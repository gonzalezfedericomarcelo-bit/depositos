<?php
// Archivo: includes/header.php
// Propósito: Encabezado con DISEÑO MEDICAL PRO + RESPONSIVE OPTIMIZADO

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$nombre_usuario_global = $_SESSION['user_name'] ?? 'Usuario';
$roles_global = $_SESSION['user_roles'] ?? [];
$rol_principal_global = !empty($roles_global) ? $roles_global[0] : 'Sin Rol';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Policlínica ACTIS - Gestión</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #0f4c75;       /* Azul Médico Oscuro */
            --secondary-color: #3282b8;     /* Azul Claro */
            --accent-color: #bbe1fa;        /* Celeste */
            --bg-color: #f4f7f6;            /* Gris clínico muy suave */
            --text-color: #2c3e50;          /* Gris oscuro legible */
            --sidebar-width: 260px;
        }

        body { 
            background-color: var(--bg-color); 
            font-family: 'Inter', sans-serif;
            color: var(--text-color);
            overflow-x: hidden; /* Evitar scroll horizontal general */
        }

        /* --- SIDEBAR --- */
        .sidebar {
            min-width: var(--sidebar-width);
            max-width: var(--sidebar-width);
            min-height: 100vh;
            background: linear-gradient(180deg, #1b263b 0%, #0d1b2a 100%);
            color: #fff;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
            z-index: 1000;
            transition: all 0.3s;
        }

        .sidebar .brand {
            padding: 25px 20px;
            text-align: center;
            background-color: rgba(0,0,0,0.2);
            font-weight: 700;
            font-size: 1.3rem;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .sidebar ul.components { padding: 15px 0; }
        
        .sidebar ul li a {
            padding: 14px 25px;
            display: block;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            border-left: 4px solid transparent;
            transition: all 0.2s ease;
        }

        .sidebar ul li a:hover, .sidebar ul li a.active {
            color: #fff;
            background: rgba(255,255,255,0.1);
            border-left: 4px solid var(--secondary-color);
        }

        .sidebar .section-title {
            color: rgba(255,255,255,0.4);
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: 700;
            padding: 20px 25px 5px;
        }

        /* --- CONTENT --- */
        .content-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* --- NAVBAR --- */
        .top-navbar {
            background: #fff;
            height: 70px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            padding: 0 30px;
            z-index: 900;
        }

        /* --- CARDS PREMIUM --- */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
            transition: transform 0.2s;
            background: #fff;
            margin-bottom: 1.5rem;
            overflow: hidden; /* Para que el header no se salga del radio */
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #edf2f7;
            padding: 1.2rem 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-body { padding: 1.5rem; }

        /* --- TABLAS --- */
        .table-responsive {
            border-radius: 8px;
        }
        .table thead th {
            background-color: #f8f9fa;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e9ecef;
            white-space: nowrap; /* Evitar que los títulos se partan */
        }
        .table td { vertical-align: middle; }

        /* --- BOTONES --- */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            letter-spacing: 0.3px;
            padding: 0.5rem 1rem;
        }
        
        .main-content { padding: 30px; flex: 1; }

        /* =========================================
           OPTIMIZACIÓN MÓVIL (Max 768px)
           ========================================= */
        @media (max-width: 768px) {
            
            /* 1. Ajustes Generales de Espacio */
            .main-content {
                padding: 15px 10px !important; /* Reducir márgenes externos drásticamente */
            }
            .container-fluid {
                padding-left: 5px !important;
                padding-right: 5px !important;
            }

            /* 2. Navbar Compacto */
            .top-navbar {
                padding: 0 15px;
                height: 60px;
            }
            .navbar-brand { display: none !important; } /* Ocultar fecha en móvil para ahorrar espacio */

            /* 3. Tarjetas Full Width */
            .card {
                border-radius: 8px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.05);
                margin-bottom: 1rem;
            }
            .card-header {
                padding: 1rem;
                font-size: 1rem;
                flex-direction: column; /* Poner título arriba y botón abajo si no caben */
                align-items: flex-start;
                gap: 10px;
            }
            .card-header button, .card-header a.btn {
                width: 100%; /* Botones de cabecera full width */
                display: block;
                text-align: center;
            }
            .card-body {
                padding: 1rem;
            }

            /* 4. Tablas Compactas */
            .table-responsive {
                border: 1px solid #eee;
            }
            .table th, .table td {
                padding: 0.5rem 0.3rem !important; /* Menos relleno */
                font-size: 0.85rem; /* Letra un poco más chica */
            }
            /* Ocultar columnas menos importantes en móvil si quieres (opcional) */
            /* .d-none-mobile { display: none; } */

            /* 5. Títulos y Textos */
            h1 { font-size: 1.5rem !important; margin-bottom: 1rem !important; text-align: center; }
            .breadcrumb { justify-content: center; display: none; } /* Ocultar breadcrumb en móvil */

            /* 6. Inputs y Formularios */
            .form-control, .form-select {
                font-size: 16px; /* Evita zoom automático en iPhone */
                padding: 0.6rem;
            }
            label { font-size: 0.9rem; margin-bottom: 0.3rem; }
            .btn-lg { width: 100%; margin-top: 10px; } /* Botones de acción grandes ocupan todo el ancho */

            /* 7. Ajuste de Alertas */
            .alert {
                font-size: 0.9rem;
                padding: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="d-flex">