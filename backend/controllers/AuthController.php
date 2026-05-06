<?php

require_once __DIR__ . '/../models/User.php';

class AuthController {

    public static function register(): void {
        $data = json_decode(file_get_contents('php://input'), true);

        $nombre   = trim($data['nombre'] ?? '');
        $email    = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');

        if (!$nombre || !$email || !$password) {
            http_response_code(400);
            echo json_encode(['error' => 'Todos los campos son obligatorios.']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Email inválido.']);
            return;
        }

        if (strlen($password) < 6) {
            http_response_code(400);
            echo json_encode(['error' => 'La contraseña debe tener al menos 6 caracteres.']);
            return;
        }

        if (User::findByEmail($email)) {
            http_response_code(409);
            echo json_encode(['error' => 'Ya existe una cuenta con ese email.']);
            return;
        }

        $id = User::create($nombre, $email, $password);
        http_response_code(201);
        echo json_encode(['message' => 'Usuario registrado correctamente.', 'id' => $id]);
    }

    public static function login(): void {
        $data = json_decode(file_get_contents('php://input'), true);

        $email    = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');

        if (!$email || !$password) {
            http_response_code(400);
            echo json_encode(['error' => 'Email y contraseña son obligatorios.']);
            return;
        }

        $user = User::findByEmail($email);

        if (!$user || !User::verifyPassword($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Credenciales incorrectas.']);
            return;
        }

        // Iniciar sesión
        session_start();
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_nombre'] = $user['nombre'];

        echo json_encode([
            'message' => 'Login exitoso.',
            'user' => [
                'id'     => $user['id'],
                'nombre' => $user['nombre'],
                'email'  => $user['email'],
            ]
        ]);
    }

    public static function logout(): void {
        session_start();
        session_destroy();
        echo json_encode(['message' => 'Sesión cerrada.']);
    }

    public static function me(): void {
        session_start();
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'No autenticado.']);
            return;
        }
        $user = User::findById((int) $_SESSION['user_id']);
        echo json_encode(['user' => $user]);
    }
}
