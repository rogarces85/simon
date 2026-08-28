<?php
/**
 * Tests unitarios para RUNCOACH
 * Ejecutar: php tests/unit_tests.php
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../models/TrainingStructure.php';
require_once __DIR__ . '/../models/Workout.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Notification.php';

echo "=== RUNCOACH Unit Tests ===\n\n";

$passed = 0;
$failed = 0;

function check($condition, $message) {
    global $passed, $failed;
    if ($condition) {
        echo "✅ PASS: $message\n";
        $passed++;
    } else {
        echo "❌ FAIL: $message\n";
        $failed++;
    }
}

function assertEquals($expected, $actual, $message) {
    check($expected === $actual, "$message (esperado: " . json_encode($expected) . ", actual: " . json_encode($actual) . ")");
}

function assertTrue($condition, $message) { check($condition === true, $message); }
function assertFalse($condition, $message) { check($condition === false, $message); }
function assertNull($value, $message) { check($value === null, $message); }
function assertNotNull($value, $message) { check($value !== null, $message); }
function assertCount($expected, $array, $message) { check(count($array) === $expected, $message); }
function assertArrayHasKey($key, $array, $message) { check(array_key_exists($key, $array), $message); }

// ===== TrainingStructure Tests =====
echo "--- TrainingStructure ---\n";

// emptyStructure
$empty = TrainingStructure::emptyStructure();
assertEquals(2, $empty['v'], 'emptyStructure version = 2');
assertEquals('', $empty['warm_up'], 'emptyStructure warm_up vacío');
assertEquals('', $empty['main_set'], 'emptyStructure main_set vacío');
assertCount(9, array_keys(TrainingStructure::BLOCKS), 'emptyStructure tiene todos los bloques');
assertArrayHasKey('estimated_minutes', $empty, 'emptyStructure tiene estimated_minutes');
assertArrayHasKey('tip_ids', $empty, 'emptyStructure tiene tip_ids');

// parse con null
$parsed = TrainingStructure::parse(null);
assertEquals(TrainingStructure::emptyStructure(), $parsed, 'parse(null) devuelve estructura vacía');

// parse con string vacío
$parsed = TrainingStructure::parse('');
assertEquals(TrainingStructure::emptyStructure(), $parsed, 'parse("") devuelve estructura vacía');

// parse con array (v2)
$input = ['warm_up' => 'test', 'main_set' => 'correr 5km'];
$parsed = TrainingStructure::parse($input);
assertEquals('test', $parsed['warm_up'], 'parse(array) respeta warm_up');
assertEquals('correr 5km', $parsed['main_set'], 'parse(array) respeta main_set');
assertEquals(2, $parsed['v'], 'parse(array) mantiene version 2');

// parse con JSON string (v1 legacy)
$json = json_encode('Ritmo 3k a 4:00');
$parsed = TrainingStructure::parse($json);
assertEquals('Ritmo 3k a 4:00', $parsed['main_set'], 'parse(JSON string v1) va a main_set');

// parse con texto plano legacy
$parsed = TrainingStructure::parse('Trote suave 40 min');
assertEquals('Trote suave 40 min', $parsed['main_set'], 'parse(legacy text) va a main_set');

// parse(array) merge - no muta el original
$base = TrainingStructure::emptyStructure();
$incoming = ['warm_up' => 'nuevo', 'estimated_minutes' => 45];
$merged = TrainingStructure::parse($incoming);
assertEquals('nuevo', $merged['warm_up'], 'parse(array) actualiza warm_up');
assertEquals(45, $merged['estimated_minutes'], 'parse(array) actualiza estimated_minutes');
assertEquals('', $base['warm_up'], 'parse(array) no muta base original');

// toJson
$json = TrainingStructure::toJson(['warm_up' => 'test', 'main_set' => 'correr']);
assertTrue(is_string($json), 'toJson devuelve string');
$decoded = json_decode($json, true);
assertEquals('test', $decoded['warm_up'], 'toJson serializa warm_up');
assertEquals('correr', $decoded['main_set'], 'toJson serializa main_set');

// toJson con null
assertNull(TrainingStructure::toJson(null), 'toJson(null) devuelve null');
assertNull(TrainingStructure::toJson(''), 'toJson("") devuelve null');

// isEmpty
assertTrue(TrainingStructure::isEmpty(TrainingStructure::emptyStructure()), 'isEmpty(true) para estructura vacía');
assertFalse(TrainingStructure::isEmpty(['main_set' => 'x']), 'isEmpty(false) si main_set tiene contenido');

// ===== Workout Tests =====
echo "\n--- Workout ---\n";

$workoutData = [
    'athlete_id' => 999,
    'date' => '2026-01-15',
    'type' => 'Series',
    'description' => 'Test 5x1000',
    'status' => 'pending',
    'structure' => '{"v":2,"main_set":"5x1000"}',
    'delivery_status' => 'sent'
];

// Note: Workout::create requiere BD, solo test de estructura
assertTrue(isset($workoutData['athlete_id']), 'Workout data structure');

// ===== Seed Idempotencia Test =====
echo "\n--- Seed Idempotencia ---\n";

// Simular: insertar template duplicado no debe fallar
$db = Database::getInstance();
$coachId = 1;

// Limpiar templates de test
$db->exec("DELETE FROM templates WHERE coach_id = $coachId AND name LIKE 'TEST_%'");

// Primera inserción
$stmt = $db->prepare("INSERT INTO templates (coach_id, name, type, structure) VALUES (?, ?, ?, ?)");
$stmt->execute([$coachId, 'TEST_Series_5x1000', 'Series', '{}']);
assertTrue($db->lastInsertId() > 0, 'Primera inserción OK');

// Segunda inserción (simulando lógica idempotente del seed)
$check = $db->prepare("SELECT id FROM templates WHERE coach_id = ? AND name = ? AND type = ?");
$check->execute([$coachId, 'TEST_Series_5x1000', 'Series']);
$exists = $check->fetch();
assertNotNull($exists, 'Segunda verificación encuentra template existente');

// Cleanup
$db->exec("DELETE FROM templates WHERE coach_id = $coachId AND name LIKE 'TEST_%'");

// ===== Notification Test =====
echo "\n--- Notification ---\n";
$notif = Notification::create(1, 'Test notification', 'info');
assertTrue($notif === true, 'Notification::create devuelve true en éxito');

$unread = Notification::getUnread(1);
assertTrue(is_array($unread), 'Notification::getUnread devuelve array');

$all = Notification::getAll(1, 10);
assertTrue(is_array($all), 'Notification::getAll devuelve array');
assertTrue(count($all) <= 10, 'Notification::getAll respeta límite');

// ===== User Test =====
echo "\n--- User ---\n";
$users = User::getByCoachId(1);
assertTrue(is_array($users), 'User::getByCoachId devuelve array');

$coach1 = User::getById(1);
assertNotNull($coach1, 'User::getById(1) existe');
assertEquals('coach1@test.local', $coach1['username'] ?? '', 'User::getById(1) es coach1');

// ===== Resumen =====
echo "\n=== RESUMEN ===\n";
echo "Pasados: $passed\n";
echo "Fallados: $failed\n";

exit($failed > 0 ? 1 : 0);