<?php
/**
 * Estructura de una sesión de entrenamiento, desglosada en bloques.
 *
 * Hasta la versión 2.1 la "estructura" era un único texto libre. Los planes
 * impresos de la carpeta planes/ muestran que las sesiones reales siempre
 * siguen la misma anatomía (entrada en calor, movilidad, técnicas, rectas,
 * trabajo principal, fortalecimiento, vuelta a la calma y elongación), así que
 * ahora se guarda como JSON con esos bloques.
 *
 * Formatos que hay que seguir soportando al leer:
 *   v2      array asociativo con las claves de BLOCKS  -> se usa tal cual
 *   v1      cadena JSON entrecomillada ("Ritmo 3k")    -> pasa a main_set
 *   legacy  texto plano sin codificar                  -> pasa a main_set
 *   null    sin estructura                             -> bloques vacíos
 *
 * Antes, una fila con texto plano hacía json_decode() === null y la instrucción
 * desaparecía sin aviso de la vista del atleta.
 */
class TrainingStructure
{
    public const VERSION = 2;

    /** Bloques en el orden en que se muestran e imprimen. */
    public const BLOCKS = [
        'warm_up' => 'Entrada en calor',
        'mobility' => 'Movilidad',
        'drills' => 'Técnicas de carrera',
        'strides' => 'Rectas / progresivos',
        'main_set' => 'Trabajo principal',
        'strength' => 'Fortalecimiento',
        'cool_down' => 'Vuelta a la calma',
        'elongation' => 'Elongación',
        'notes' => 'Notas',
    ];

    /** Ritmo de referencia (segundos por km) cuando el texto no declara ninguno. */
    private const DEFAULT_PACE = 360; // 6:00 min/km

    public static function emptyStructure()
    {
        $blocks = ['v' => self::VERSION];
        foreach (array_keys(self::BLOCKS) as $key) {
            $blocks[$key] = '';
        }
        $blocks['estimated_minutes'] = null;
        $blocks['estimated_km'] = null;
        $blocks['tip_ids'] = [];
        return $blocks;
    }

    /**
     * Normaliza cualquiera de los formatos históricos al formato v2.
     * Nunca devuelve null: si no hay nada, devuelve la estructura vacía.
     */
    public static function parse($raw)
    {
        $result = self::emptyStructure();

        if ($raw === null || $raw === '') {
            return $result;
        }

        // Ya viene como array (por ejemplo desde el formulario).
        if (is_array($raw)) {
            return self::merge($result, $raw);
        }

        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {
            return self::merge($result, $decoded);
        }

        if (is_string($decoded)) {
            // v1: el texto plano se había pasado por json_encode.
            $result['main_set'] = $decoded;
            return $result;
        }

        // legacy: texto plano sin codificar.
        $result['main_set'] = (string) $raw;
        return $result;
    }

    private static function merge(array $base, array $incoming)
    {
        foreach (array_keys(self::BLOCKS) as $key) {
            if (isset($incoming[$key])) {
                $base[$key] = trim((string) $incoming[$key]);
            }
        }

        if (isset($incoming['estimated_minutes']) && $incoming['estimated_minutes'] !== '') {
            $base['estimated_minutes'] = (int) $incoming['estimated_minutes'];
        }
        if (isset($incoming['estimated_km']) && $incoming['estimated_km'] !== '') {
            $base['estimated_km'] = (float) $incoming['estimated_km'];
        }
        if (isset($incoming['tip_ids']) && is_array($incoming['tip_ids'])) {
            $base['tip_ids'] = array_values(array_unique(array_map('intval', $incoming['tip_ids'])));
        }

        $base['v'] = self::VERSION;
        return $base;
    }

    /**
     * Serialización única para templates.structure y workouts.structure. Antes
     * una tabla guardaba texto plano y la otra JSON, y cada lector improvisaba.
     */
    public static function toJson($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        $blocks = self::parse($value);
        return json_encode($blocks, JSON_UNESCAPED_UNICODE);
    }

    /** Verdadero si no hay ni un bloque con contenido. */
    public static function isEmpty(array $blocks)
    {
        foreach (array_keys(self::BLOCKS) as $key) {
            if (trim((string) ($blocks[$key] ?? '')) !== '') {
                return false;
            }
        }
        return true;
    }

    /** Los bloques con contenido, en orden, como [clave, etiqueta, texto]. */
    public static function filledBlocks(array $blocks)
    {
        $out = [];
        foreach (self::BLOCKS as $key => $label) {
            $text = trim((string) ($blocks[$key] ?? ''));
            if ($text !== '') {
                $out[] = ['key' => $key, 'label' => $label, 'text' => $text];
            }
        }
        return $out;
    }

    /** Versión de una línea, para listados y correos. */
    public static function summary(array $blocks, $maxLength = 120)
    {
        $text = trim((string) ($blocks['main_set'] ?? ''));
        if ($text === '') {
            $filled = self::filledBlocks($blocks);
            $text = $filled ? $filled[0]['text'] : '';
        }
        $text = preg_replace('/\s+/u', ' ', $text);
        if (mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength - 1) . '…';
        }
        return $text;
    }

    // ---------------------------------------------------------------
    // Estimación de carga
    // ---------------------------------------------------------------

    /**
     * Minutos estimados de la sesión. Si el coach fijó estimated_minutes a mano
     * se respeta ese valor; si no, se deduce del texto de los bloques.
     * Es una ayuda para el validador de carga, no una medida exacta.
     */
    public static function estimateMinutes(array $blocks)
    {
        if (!empty($blocks['estimated_minutes'])) {
            return (int) $blocks['estimated_minutes'];
        }

        $total = 0;
        foreach (array_keys(self::BLOCKS) as $key) {
            if ($key === 'notes') {
                continue;
            }
            $total += self::minutesInText((string) ($blocks[$key] ?? ''));
        }

        return $total > 0 ? (int) round($total) : 0;
    }

    /** Kilómetros estimados: los declarados a mano o los que aparezcan en el texto. */
    public static function estimateKm(array $blocks)
    {
        if (!empty($blocks['estimated_km'])) {
            return (float) $blocks['estimated_km'];
        }

        $total = 0.0;
        foreach (array_keys(self::BLOCKS) as $key) {
            if ($key === 'notes') {
                continue;
            }
            $total += self::kmInText((string) ($blocks[$key] ?? ''));
        }

        return $total > 0 ? round($total, 2) : 0.0;
    }

    /**
     * Quita los ritmos del texto para que no se confundan con duraciones:
     * "20' trote 6:30/6:00min/km" tiene una duración (20') y un ritmo, no dos.
     */
    private static function stripPaces($text)
    {
        $text = preg_replace('#\d{1,2}:\s*\d{2}\s*/\s*\d{1,2}:\s*\d{2}\s*min\s*/\s*km#iu', ' ', $text);
        $text = preg_replace('#\d{1,2}:\s*\d{2}\s*min\s*/\s*km#iu', ' ', $text);
        return $text;
    }

    /** Ritmo medio declarado en el texto, en segundos por km. */
    private static function paceInText($text)
    {
        if (preg_match('#(\d{1,2}):\s*(\d{2})\s*/\s*(\d{1,2}):\s*(\d{2})\s*min\s*/\s*km#iu', $text, $m)) {
            $a = (int) $m[1] * 60 + (int) $m[2];
            $b = (int) $m[3] * 60 + (int) $m[4];
            return ($a + $b) / 2;
        }
        if (preg_match('#(\d{1,2}):\s*(\d{2})\s*min\s*/\s*km#iu', $text, $m)) {
            return (int) $m[1] * 60 + (int) $m[2];
        }
        return self::DEFAULT_PACE;
    }

    /** Apóstrofos usados para "minutos" en los planes: recto y tipográfico. */
    private const TICK = '[\x{0027}\x{2019}\x{00B4}]';

    private static function minutesInText($text)
    {
        if (trim($text) === '') {
            return 0;
        }

        $tick = self::TICK;
        $pace = self::paceInText($text);
        $clean = self::stripPaces($text);
        $minutes = 0.0;

        // Series con tiempo por repetición: "5x1000m (5' los 1000m)", "10x400m (2')".
        $pattern = '#(\d+)\s*x\s*\d+\s*m[^()]{0,30}\((\d+)\s*(?:' . $tick . ')?\s*(\d{2})?#u';
        if (preg_match_all($pattern, $clean, $reps, PREG_SET_ORDER)) {
            foreach ($reps as $r) {
                $perRep = (int) $r[2] + (isset($r[3]) && $r[3] !== '' ? (int) $r[3] / 60 : 0);
                $minutes += (int) $r[1] * $perRep;
                $clean = str_replace($r[0], ' ', $clean);
            }
        }

        // Recuperaciones: "recuperación 3' trote", "rec 2' caminando".
        $pattern = '#(?:rec(?:uperaci[oó]n)?)\.?\s*(\d+)\s*' . $tick . '#iu';
        if (preg_match_all($pattern, $clean, $recs, PREG_SET_ORDER)) {
            foreach ($recs as $r) {
                $minutes += (int) $r[1];
                $clean = str_replace($r[0], ' ', $clean);
            }
        }

        // Horas: "1h30", "2h".
        if (preg_match_all('#(\d+)\s*h\s*(\d{1,2})?#iu', $clean, $hours, PREG_SET_ORDER)) {
            foreach ($hours as $h) {
                $minutes += (int) $h[1] * 60 + (isset($h[2]) && $h[2] !== '' ? (int) $h[2] : 0);
                $clean = str_replace($h[0], ' ', $clean);
            }
        }

        // Rangos "20/25'" -> promedio.
        $pattern = '#(\d+)\s*/\s*(\d+)\s*' . $tick . '#u';
        if (preg_match_all($pattern, $clean, $ranges, PREG_SET_ORDER)) {
            foreach ($ranges as $r) {
                $minutes += ((int) $r[1] + (int) $r[2]) / 2;
                $clean = str_replace($r[0], ' ', $clean);
            }
        }

        // Duraciones sueltas: "40'", "30 min".
        $pattern = '#(\d+)\s*(?:' . $tick . '|min(?:utos)?\b)#iu';
        if (preg_match_all($pattern, $clean, $mins, PREG_SET_ORDER)) {
            foreach ($mins as $m) {
                $minutes += (int) $m[1];
                $clean = str_replace($m[0], ' ', $clean);
            }
        }

        // Distancias sin tiempo asociado: "8km", "10 km" -> se convierten con el ritmo.
        if (preg_match_all('#(\d+(?:[.,]\d+)?)\s*km\b#iu', $clean, $kms, PREG_SET_ORDER)) {
            foreach ($kms as $k) {
                $minutes += ((float) str_replace(',', '.', $k[1])) * $pace / 60;
            }
        }

        return $minutes;
    }

    private static function kmInText($text)
    {
        if (trim($text) === '') {
            return 0.0;
        }

        $clean = self::stripPaces($text);
        $km = 0.0;

        // Series en metros: "5x1000m", "6x100m".
        if (preg_match_all('#(\d+)\s*x\s*(\d+)\s*m\b#iu', $clean, $reps, PREG_SET_ORDER)) {
            foreach ($reps as $r) {
                $km += (int) $r[1] * (int) $r[2] / 1000;
                $clean = str_replace($r[0], ' ', $clean);
            }
        }

        // Distancias directas: "8km", "10,5 km".
        if (preg_match_all('#(\d+(?:[.,]\d+)?)\s*km\b#iu', $clean, $kms, PREG_SET_ORDER)) {
            foreach ($kms as $k) {
                $km += (float) str_replace(',', '.', $k[1]);
            }
        }

        return $km;
    }
}
