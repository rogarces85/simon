<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/Mailer.php';
require_once 'models/User.php';
require_once 'models/Workout.php';
require_once 'models/Notification.php';
Auth::init();
Auth::requireRole('coach');

$coach = Auth::user();
$db = Database::getInstance();

// Get athletes for dropdown
$athletes = User::getByCoachId($coach['id']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Generate plan
    if ($_POST['action'] === 'generate_plan') {
        $athleteId = $_POST['athlete_id'];
        $weekStart = $_POST['week_start'];
        $days = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        $createdWorkouts = [];

        foreach ($days as $i => $day) {
            $templateId = $_POST['template_' . $day] ?? null;
            if ($templateId) {
                $stmt = $db->prepare("SELECT * FROM templates WHERE id = ?");
                $stmt->execute([$templateId]);
                $template = $stmt->fetch();

                if ($template) {
                    $workoutDate = date('Y-m-d', strtotime($weekStart . " + $i days"));

                    // Use customized structure if provided, otherwise use template default
                    $customStructure = $_POST['structure_' . $day] ?? null;
                    $finalStructure = !empty($customStructure) ? $customStructure : $template['structure'];

                    $workoutData = [
                        'athlete_id' => $athleteId,
                        'date' => $workoutDate,
                        'type' => $template['type'],
                        'description' => $template['name'],
                        'status' => 'pending',
                        'structure' => $finalStructure,
                        'delivery_status' => 'sent'
                    ];
                    Workout::create($workoutData);
                    $createdWorkouts[] = [
                        'date' => $workoutDate,
                        'type' => $template['type'],
                        'description' => $template['name']
                    ];
                }
            }
        }

        if (!empty($createdWorkouts)) {
            $athlete = User::getById($athleteId);
            if ($athlete && $athlete['username']) {
                Mailer::sendNewPlanNotification(
                    $athlete['username'],
                    $athlete['name'],
                    $coach['name'],
                    $weekStart,
                    $createdWorkouts
                );
                $msg = "📋 Tu entrenador ha generado un nuevo plan de entrenamiento para la semana del " . (new DateTime($weekStart))->format('d/m/Y');
                Notification::create($athleteId, $msg, 'info');
            }
        }
        header('Location: generar_plan.php?success=plan');
        exit;
    }

    // Template CRUD
    if ($_POST['action'] === 'create_template') {
        $sql = "INSERT INTO templates (coach_id, name, type, block_type, structure) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $coach['id'],
            $_POST['name'],
            $_POST['type'],
            $_POST['block_type'] ?? null,
            $_POST['structure'] ?? null
        ]);
        header('Location: generar_plan.php?tab=plantillas&success=template_created');
        exit;
    }

    if ($_POST['action'] === 'update_template') {
        $sql = "UPDATE templates SET name = ?, type = ?, block_type = ?, structure = ? WHERE id = ? AND coach_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $_POST['name'],
            $_POST['type'],
            $_POST['block_type'] ?? null,
            $_POST['structure'] ?? null,
            $_POST['template_id'],
            $coach['id']
        ]);
        header('Location: generar_plan.php?tab=plantillas&success=template_updated');
        exit;
    }

    if ($_POST['action'] === 'delete_template') {
        $sql = "DELETE FROM templates WHERE id = ? AND coach_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$_POST['template_id'], $coach['id']]);
        header('Location: generar_plan.php?tab=plantillas&success=template_deleted');
        exit;
    }
}

// Get templates
$stmt = $db->prepare("SELECT * FROM templates WHERE coach_id = ? ORDER BY type, name");
$stmt->execute([$coach['id']]);
$templates = $stmt->fetchAll();

$activeTab = $_GET['tab'] ?? 'plan';

include 'views/layout/header.php';
// Include the modular view
include 'views/generar_plan_view.php';
include 'views/layout/footer.php';
?>