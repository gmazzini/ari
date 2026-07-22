<?php

declare(strict_types=1);

include "/home/www/info/ari_local.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$con = mysqli_connect(
    $dbhost,
    $dbuser,
    $dbpassword,
    $dbname
);

mysqli_set_charset($con, "utf8mb4");


function norm(string $input): ?string
{
    /*
     * Divide il campo usando i separatori normalmente presenti
     * tra più numeri telefonici.
     */
    $parts = preg_split(
        '/\s*(?:\/+|\|+|\bor\b|\boppure\b)\s*/i',
        $input
    );

    $nums = [];

    foreach ($parts as $p) {
        /*
         * Conserva soltanto le cifre, mantenendo gli eventuali
         * zeri iniziali.
         */
        $n = preg_replace('/\D+/', '', $p);

        if ($n !== '' && strlen($n) >= 6) {
            $nums[] = $n;
        }
    }

    if (count($nums) >= 2) {
        return $nums[0] . '-' . $nums[1];
    }

    if (count($nums) === 1) {
        return $nums[0];
    }

    return null;
}


/*
 * Converte una matricola nel formato NN-NNNN
 * nel formato interno NNNNNN.
 */
function matricolaId(string $matricola): ?string
{
    $matricola = trim($matricola);

    if (!preg_match('/^(\d{2})-(\d{4})$/', $matricola, $m)) {
        return null;
    }

    return $m[1] . $m[2];
}


/*
 * Converte una sezione nel formato NN-NN
 * nel formato interno NNNN.
 */
function sezioneId(string $sezione): ?string
{
    $sezione = trim($sezione);

    if (!preg_match('/^(\d{2})-(\d{2})$/', $sezione, $m)) {
        return null;
    }

    return $m[1] . $m[2];
}


/*
 * Ripristina gli eventuali zeri iniziali del CAP persi
 * durante l'esportazione da Excel.
 *
 * Esempi:
 *
 * 167  -> 00167
 * 4018 -> 04018
 * 1100 -> 01100
 */
function normalizzaCap(string $cap): string
{
    $cap = trim($cap);

    if (
        $cap !== '' &&
        ctype_digit($cap) &&
        strlen($cap) <= 5
    ) {
        return str_pad(
            $cap,
            5,
            '0',
            STR_PAD_LEFT
        );
    }

    return $cap;
}


function openFileOrFail(string $filename, string $mode)
{
    $fp = fopen($filename, $mode);

    if ($fp === false) {
        throw new RuntimeException(
            "Impossibile aprire il file: $filename"
        );
    }

    return $fp;
}


try {
    mysqli_begin_transaction($con);

    mysqli_query(
        $con,
        "DELETE FROM soci"
    );


    /*
     * ============================================================
     * IMPORTAZIONE DI TUTTI I SOCI
     * ============================================================
     */

    echo "--- Tutti\n";

    $insertSocio = mysqli_prepare(
        $con,
        "
        INSERT INTO soci
        (
            id,
            nome,
            cf,
            nascita,
            callsign,
            flag,
            sezione,
            q0,
            q1,
            q2,
            voto,
            email,
            numeri,
            indirizzo
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
        "
    );

    $fp = openFileOrFail("q5.csv", "r");

    /*
     * Salta l'intestazione del CSV.
     */
    fgetcsv($fp, 0, ',', '"', '');

    $record = 1;
    $importati = 0;
    $scartati = 0;
    $seenIds = [];

    while (($oo = fgetcsv($fp, 0, ',', '"', '')) !== false) {
        $record++;

        /*
         * Salta eventuali record completamente vuoti.
         */
        if (
            count($oo) === 1 &&
            trim((string)$oo[0]) === ''
        ) {
            continue;
        }

        /*
         * Struttura attesa:
         *
         *  0 Elenco
         *  1 Nominativo
         *  2 Indirizzo
         *  3 CAP
         *  4 Citta
         *  5 Provincia
         *  6 Email
         *  7 Telefono
         *  8 Nato
         *  9 Codice Fiscale
         * 10 Sigla1
         * 11 Sigla2
         * 12 Varie
         * 13 QSL
         * 14 Sezione
         * 15 Voto_
         * 16 Matricola
         * 17 Q2
         * 18 Q1
         * 19 Q0
         */
        if (count($oo) !== 20) {
            fprintf(
                STDERR,
                "q5.csv: record %d non valido: attese 20 colonne, trovate %d\n",
                $record,
                count($oo)
            );

            $scartati++;
            continue;
        }

        $matricolaOriginale = trim((string)$oo[16]);
        $id = matricolaId($matricolaOriginale);

        if ($id === null) {
            fprintf(
                STDERR,
                "q5.csv: record %d, matricola non valida: [%s]\n",
                $record,
                $matricolaOriginale
            );

            $scartati++;
            continue;
        }

        /*
         * Evita errori SQL se nel file compare due volte
         * la stessa matricola.
         */
        if (isset($seenIds[$id])) {
            fprintf(
                STDERR,
                "q5.csv: record %d, matricola duplicata: [%s]\n",
                $record,
                $matricolaOriginale
            );

            $scartati++;
            continue;
        }

        $seenIds[$id] = true;

        $nome = trim((string)$oo[1]);

        /*
         * Composizione dell'indirizzo.
         */
        $via = trim((string)$oo[2]);
        $cap = normalizzaCap((string)$oo[3]);
        $citta = trim((string)$oo[4]);
        $provincia = trim((string)$oo[5]);

        $indirizzo = implode(
            "-",
            [
                $via,
                $cap,
                $citta,
                $provincia
            ]
        );

        $email = trim((string)$oo[6]);

        $numeri = norm((string)$oo[7]) ?? '';

        $nascita = (int)((float)$oo[8]);

        /*
         * Conserva la prima parte del codice fiscale nel caso
         * contenga accidentalmente una virgola.
         */
        $vv = str_getcsv(
            (string)$oo[9],
            ',',
            '"',
            ''
        );

        $cf = str_replace(
            ' ',
            '',
            trim((string)($vv[0] ?? ''))
        );

        $callsign = trim((string)$oo[10]);
        $flag = trim((string)$oo[12]);

        $sezioneOriginale = trim((string)$oo[14]);
        $sezione = sezioneId($sezioneOriginale);

        if ($sezione === null) {
            fprintf(
                STDERR,
                "q5.csv: record %d, sezione non valida per %s: [%s]\n",
                $record,
                $matricolaOriginale,
                $sezioneOriginale
            );

            /*
             * Il socio viene comunque importato lasciando
             * vuota la sezione.
             */
            $sezione = '';
        }

        $voto = (
            strcasecmp(
                trim((string)$oo[15]),
                'Si'
            ) === 0
        ) ? 1 : 0;

        $q2 = trim((string)$oo[17]);
        $q1 = trim((string)$oo[18]);
        $q0 = trim((string)$oo[19]);

        mysqli_stmt_bind_param(
            $insertSocio,
            "sssissssssisss",
            $id,
            $nome,
            $cf,
            $nascita,
            $callsign,
            $flag,
            $sezione,
            $q0,
            $q1,
            $q2,
            $voto,
            $email,
            $numeri,
            $indirizzo
        );

        mysqli_stmt_execute($insertSocio);

        $importati++;
    }

    fclose($fp);
    mysqli_stmt_close($insertSocio);

    echo "Importati: $importati\n";

    if ($scartati > 0) {
        echo "Scartati: $scartati\n";
    }


    /*
     * ============================================================
     * IMPORTAZIONE THR
     * ============================================================
     */

    echo "--- THR\n";

    $updateThr = mysqli_prepare(
        $con,
        "UPDATE soci SET thr = 1 WHERE id = ?"
    );

    $fp = openFileOrFail("q3.csv", "r");

    /*
     * Salta l'intestazione.
     */
    fgetcsv($fp, 0, ',', '"', '');

    $record = 1;
    $thrAggiornati = 0;

    while (($oo = fgetcsv($fp, 0, ',', '"', '')) !== false) {
        $record++;

        if (
            count($oo) === 1 &&
            trim((string)$oo[0]) === ''
        ) {
            continue;
        }

        if (count($oo) < 1) {
            continue;
        }

        $matricolaOriginale = trim((string)$oo[0]);
        $id = matricolaId($matricolaOriginale);

        if ($id === null) {
            fprintf(
                STDERR,
                "q3.csv: record %d, matricola non valida: [%s]\n",
                $record,
                $matricolaOriginale
            );

            continue;
        }

        mysqli_stmt_bind_param(
            $updateThr,
            "s",
            $id
        );

        mysqli_stmt_execute($updateThr);

        $thrAggiornati +=
            mysqli_stmt_affected_rows($updateThr);
    }

    fclose($fp);
    mysqli_stmt_close($updateThr);

    echo "THR aggiornati: $thrAggiornati\n";


    /*
     * ============================================================
     * IMPORTAZIONE GRUPPI FAMILIARI
     * ============================================================
     */

    echo "--- FAMILY\n";

    $updateFamily = mysqli_prepare(
        $con,
        "
        UPDATE soci
        SET family =
            CASE
                WHEN family IS NULL OR family = ''
                    THEN ?
                ELSE CONCAT(family, '+', ?)
            END
        WHERE id = ?
        "
    );

    $fp = openFileOrFail("q4.txt", "r");

    $famiglieAggiornate = 0;

    while (($line = fgets($fp)) !== false) {
        /*
         * Vengono considerate soltanto le righe che iniziano con:
         *
         * 000032        100511
         *
         * cioè matricola del capofamiglia e matricola
         * del familiare.
         */
        if (
            !preg_match(
                '/^(\d{6})\s+(\d{6})\b/',
                $line,
                $matches
            )
        ) {
            continue;
        }

        $p1 = $matches[1];
        $p2 = $matches[2];

        /*
         * Aggiunge il capofamiglia alla scheda del familiare.
         */
        mysqli_stmt_bind_param(
            $updateFamily,
            "sss",
            $p1,
            $p1,
            $p2
        );

        mysqli_stmt_execute($updateFamily);

        $famiglieAggiornate +=
            mysqli_stmt_affected_rows($updateFamily);

        /*
         * Aggiunge il familiare alla scheda del capofamiglia.
         */
        mysqli_stmt_bind_param(
            $updateFamily,
            "sss",
            $p2,
            $p2,
            $p1
        );

        mysqli_stmt_execute($updateFamily);

        $famiglieAggiornate +=
            mysqli_stmt_affected_rows($updateFamily);
    }

    fclose($fp);
    mysqli_stmt_close($updateFamily);

    echo "Relazioni familiari aggiornate: $famiglieAggiornate\n";


    mysqli_commit($con);

    echo "--- COMPLETATO\n";

} catch (Throwable $e) {
    mysqli_rollback($con);

    fprintf(
        STDERR,
        "ERRORE: %s\n",
        $e->getMessage()
    );

    mysqli_close($con);
    exit(1);
}

mysqli_close($con);
