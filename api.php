<?php
// ══════════════════════════════════════════════
//  ECOMUNDO · API Backend
//  Archivo: api.php
// ══════════════════════════════════════════════

require_once 'db.php';

$action = $_GET['action'] ?? '';

switch ($action) {

    // ─────────────────────────────────────────
    //  POST: guardar mensaje de contacto
    // ─────────────────────────────────────────
    case 'guardar_contacto':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { badMethod(); break; }
        $data       = json_decode(file_get_contents('php://input'), true);
        $nombre     = trim($data['nombre']     ?? '');
        $correo     = trim($data['correo']     ?? '');
        $contrasena = trim($data['contrasena'] ?? '');
        $ecosistema = trim($data['ecosistema'] ?? '');
        $comentario = trim($data['comentario'] ?? '');

        if (!$nombre || !$correo || !$contrasena || !$ecosistema) {
            echo json_encode(['ok' => false, 'msg' => 'Faltan campos obligatorios.']); break;
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['ok' => false, 'msg' => 'Correo inválido.']); break;
        }
        if (strlen($contrasena) < 6) {
            echo json_encode(['ok' => false, 'msg' => 'La contraseña debe tener al menos 6 caracteres.']); break;
        }

        $db  = getDB();
        $chk = $db->prepare("SELECT id FROM contactos WHERE correo = :correo LIMIT 1");
        $chk->execute([':correo' => $correo]);
        if ($chk->fetch()) {
            echo json_encode(['ok' => false, 'msg' => 'Este correo ya está registrado. Puedes cambiar tu contraseña más abajo.']); break;
        }

        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO contactos (nombre, correo, contrasena, ecosistema, comentario)
                      VALUES (:nombre, :correo, :contrasena, :ecosistema, :comentario)")
           ->execute([':nombre'=>$nombre,':correo'=>$correo,':contrasena'=>$hash,':ecosistema'=>$ecosistema,':comentario'=>$comentario]);

        registrarVisita($db);
        echo json_encode(['ok' => true, 'msg' => '¡Mensaje enviado correctamente!']);
        break;

    // ─────────────────────────────────────────
    //  POST: cambiar contraseña
    // ─────────────────────────────────────────
    case 'cambiar_contrasena':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { badMethod(); break; }
        $data   = json_decode(file_get_contents('php://input'), true);
        $correo = trim($data['correo'] ?? '');
        $actual = trim($data['actual'] ?? '');
        $nueva  = trim($data['nueva']  ?? '');

        if (!$correo || !$actual || !$nueva) {
            echo json_encode(['ok' => false, 'msg' => 'Completa todos los campos.']); break;
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['ok' => false, 'msg' => 'Correo inválido.']); break;
        }
        if (strlen($nueva) < 6) {
            echo json_encode(['ok' => false, 'msg' => 'La nueva contraseña debe tener al menos 6 caracteres.']); break;
        }
        if ($actual === $nueva) {
            echo json_encode(['ok' => false, 'msg' => 'La nueva contraseña debe ser diferente a la actual.']); break;
        }

        $db   = getDB();
        $stmt = $db->prepare("SELECT id, contrasena FROM contactos WHERE correo = :correo LIMIT 1");
        $stmt->execute([':correo' => $correo]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['ok' => false, 'msg' => 'No encontramos una cuenta con ese correo.']); break;
        }
        if (!password_verify($actual, $user['contrasena'])) {
            echo json_encode(['ok' => false, 'msg' => 'La contraseña actual es incorrecta.']); break;
        }

        $db->prepare("UPDATE contactos SET contrasena = :hash WHERE id = :id")
           ->execute([':hash' => password_hash($nueva, PASSWORD_DEFAULT), ':id' => $user['id']]);

        echo json_encode(['ok' => true, 'msg' => '¡Contraseña actualizada exitosamente!']);
        break;

    // ─────────────────────────────────────────
    //  POST: guardar resultado del quiz
    // ─────────────────────────────────────────
    case 'guardar_quiz':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { badMethod(); break; }
        $data = json_decode(file_get_contents('php://input'), true);
        getDB()->prepare("INSERT INTO quiz_resultados (aciertos, total_preguntas, porcentaje)
                          VALUES (:a, :t, :p)")
               ->execute([':a'=>(int)($data['aciertos']??0),':t'=>(int)($data['total']??10),':p'=>(int)($data['porcentaje']??0)]);
        echo json_encode(['ok' => true]);
        break;

    // ─────────────────────────────────────────
    //  POST: suscribir al newsletter
    // ─────────────────────────────────────────
    case 'suscribir_newsletter':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { badMethod(); break; }
        $data   = json_decode(file_get_contents('php://input'), true);
        $correo = trim($data['correo'] ?? '');

        if (!$correo || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['ok' => false, 'msg' => 'Correo inválido.']); break;
        }

        $db  = getDB();
        $chk = $db->prepare("SELECT id FROM newsletter WHERE correo = :correo LIMIT 1");
        $chk->execute([':correo' => $correo]);
        if ($chk->fetch()) {
            echo json_encode(['ok' => false, 'msg' => 'Este correo ya está suscrito. ¡Gracias por tu interés!']); break;
        }

        $db->prepare("INSERT INTO newsletter (correo) VALUES (:correo)")->execute([':correo' => $correo]);
        echo json_encode(['ok' => true, 'msg' => '¡Suscrito exitosamente! Bienvenido a EcoMundo 🌿']);
        break;

    // ─────────────────────────────────────────
    //  POST: guardar comentario público
    // ─────────────────────────────────────────
    case 'guardar_comentario':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { badMethod(); break; }
        $data   = json_decode(file_get_contents('php://input'), true);
        $nombre = trim($data['nombre'] ?? '');
        $tema   = trim($data['tema']   ?? '');
        $texto  = trim($data['texto']  ?? '');

        if (!$nombre || !$texto) {
            echo json_encode(['ok' => false, 'msg' => 'Nombre y comentario son obligatorios.']); break;
        }

        $db = getDB();
        $db->prepare("INSERT INTO comentarios (nombre, tema, texto) VALUES (:nombre, :tema, :texto)")
           ->execute([':nombre'=>$nombre,':tema'=>$tema,':texto'=>$texto]);

        echo json_encode(['ok' => true, 'id' => $db->lastInsertId(), 'fecha' => date('Y-m-d H:i:s')]);
        break;

    // ─────────────────────────────────────────
    //  GET: obtener comentarios
    // ─────────────────────────────────────────
    case 'get_comentarios':
        $stmt = getDB()->query("SELECT id, nombre, tema, texto, fecha FROM comentarios ORDER BY fecha DESC");
        echo json_encode(['ok' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ─────────────────────────────────────────
    //  POST: eliminar comentario propio
    // ─────────────────────────────────────────
    case 'eliminar_comentario':
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = (int)($data['id'] ?? 0);
        if (!$id) { echo json_encode(['ok' => false, 'msg' => 'ID inválido']); break; }
        getDB()->prepare("DELETE FROM comentarios WHERE id = :id")->execute([':id' => $id]);
        echo json_encode(['ok' => true]);
        break;

    // ─────────────────────────────────────────
    //  GET: datos panel Admin
    // ─────────────────────────────────────────
    case 'admin_datos':
        $pwd = $_GET['pwd'] ?? '';
        // ⚠️ CAMBIA ESTA CONTRASEÑA
        if ($pwd !== 'ecomundo2025') {
            http_response_code(403);
            echo json_encode(['ok' => false, 'msg' => 'Contraseña incorrecta.']);
            break;
        }

        $db = getDB();

        $contactos = $db->query(
            "SELECT id, nombre, correo, ecosistema, comentario, fecha FROM contactos ORDER BY fecha DESC"
        )->fetchAll();

        $visitas = $db->query("SELECT SUM(total) AS total FROM visitas")->fetch()['total'] ?? 0;

        $quiz      = $db->query(
            "SELECT id, aciertos, total_preguntas, porcentaje, fecha FROM quiz_resultados ORDER BY fecha DESC LIMIT 200"
        )->fetchAll();
        $quizTotal = $db->query("SELECT COUNT(*) AS cnt FROM quiz_resultados")->fetch()['cnt'] ?? 0;

        $newsletter = $db->query("SELECT id, correo, fecha FROM newsletter ORDER BY fecha DESC")->fetchAll();

        echo json_encode([
            'ok'               => true,
            'contactos'        => $contactos,
            'visitas'          => (int)$visitas,
            'quiz'             => $quiz,
            'quiz_total'       => (int)$quizTotal,
            'newsletter'       => $newsletter,
            'newsletter_total' => count($newsletter),
        ]);
        break;

    // ─────────────────────────────────────────
    //  POST: limpiar mensajes (Admin)
    // ─────────────────────────────────────────
    case 'admin_limpiar':
        $data = json_decode(file_get_contents('php://input'), true);
        if (($data['pwd'] ?? '') !== 'ecomundo2025') {
            http_response_code(403);
            echo json_encode(['ok' => false, 'msg' => 'Acceso denegado.']); break;
        }
        getDB()->exec("DELETE FROM contactos");
        echo json_encode(['ok' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Acción no reconocida: ' . htmlspecialchars($action)]);
}

function registrarVisita(PDO $db): void {
    $db->prepare("INSERT INTO visitas (fecha, total) VALUES (:f, 1) ON DUPLICATE KEY UPDATE total = total + 1")
       ->execute([':f' => date('Y-m-d')]);
}

function badMethod(): void {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido.']);
}
