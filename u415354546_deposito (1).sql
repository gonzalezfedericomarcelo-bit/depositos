-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 11-12-2025 a las 13:57:23
-- Versión del servidor: 11.8.3-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u415354546_deposito`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `adjuntos`
--

CREATE TABLE `adjuntos` (
  `id` int(11) NOT NULL,
  `entidad_tipo` enum('orden_compra','entrega','usuario','pedido_servicio') NOT NULL,
  `id_entidad` int(11) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `fecha_subida` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `config_flujos`
--

CREATE TABLE `config_flujos` (
  `id` int(11) NOT NULL,
  `nombre_proceso` varchar(50) NOT NULL,
  `paso_orden` int(11) NOT NULL,
  `nombre_estado` varchar(50) NOT NULL,
  `etiqueta_estado` varchar(100) NOT NULL,
  `id_rol_responsable` int(11) NOT NULL,
  `requiere_firma` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Volcado de datos para la tabla `config_flujos`
--

INSERT INTO `config_flujos` (`id`, `nombre_proceso`, `paso_orden`, `nombre_estado`, `etiqueta_estado`, `id_rol_responsable`, `requiere_firma`) VALUES
(1, 'adquisicion_insumos', 1, 'revision_encargado', 'Revisión Encargado Insumos', 4, 0),
(2, 'adquisicion_insumos', 2, 'aprobacion_director', 'Aprobación Director Médico', 7, 1),
(3, 'adquisicion_insumos', 3, 'gestion_compras', 'Gestión de Compras', 2, 0),
(4, 'movimiento_insumos', 1, 'revision_inicial', 'Revisión Inicial (Encargado)', 4, 0),
(5, 'movimiento_insumos', 2, 'aprobacion_director', 'Autorización Director', 7, 1),
(6, 'movimiento_insumos', 3, 'preparacion_retiro', 'Preparación para Retiro', 4, 0),
(7, 'adquisicion_suministros', 1, 'revision_encargado', 'Centralización Encargado', 5, 0),
(8, 'adquisicion_suministros', 2, 'aprobacion_operativo', 'Aprobación Dir. Operativo', 8, 1),
(9, 'adquisicion_suministros', 3, 'gestion_compras', 'Gestión de Compras', 2, 0),
(10, 'movimiento_suministros', 1, 'autorizacion_logistica', 'Autorización Logística', 3, 0),
(11, 'movimiento_suministros', 2, 'preparacion_retiro', 'Preparación Entrega', 5, 0),
(12, 'movimiento_insumos', 4, 'confirmacion_recepcion', 'Confirmación de Recepción (Servicio)', 0, 0),
(13, 'movimiento_suministros', 3, 'confirmacion_recepcion', 'Confirmación de Recepción (Servicio)', 0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `config_procesos`
--

CREATE TABLE `config_procesos` (
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `id_rol_iniciador` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Volcado de datos para la tabla `config_procesos`
--

INSERT INTO `config_procesos` (`codigo`, `nombre`, `id_rol_iniciador`) VALUES
('adquisicion_insumos', 'Adquisición Insumos Médicos', 0),
('adquisicion_suministros', 'Adquisición Suministros Grales', 0),
('movimiento_insumos', 'Movimiento Interno Insumos', 0),
('movimiento_suministros', 'Movimiento Interno Suministros', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entregas`
--

CREATE TABLE `entregas` (
  `id` int(11) NOT NULL,
  `tipo_origen` enum('insumos','suministros') NOT NULL,
  `id_usuario_responsable` int(11) NOT NULL,
  `solicitante_nombre` varchar(100) NOT NULL,
  `solicitante_area` varchar(100) NOT NULL,
  `firma_solicitante_data` longtext DEFAULT NULL,
  `fecha_entrega` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `entregas`
--

INSERT INTO `entregas` (`id`, `tipo_origen`, `id_usuario_responsable`, `solicitante_nombre`, `solicitante_area`, `firma_solicitante_data`, `fecha_entrega`) VALUES
(1, 'insumos', 1, 'sffdgfd', 'Quirófano', 'data:image/png;base64,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', '2025-12-10 12:24:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entregas_items`
--

CREATE TABLE `entregas_items` (
  `id` int(11) NOT NULL,
  `id_entrega` int(11) NOT NULL,
  `id_insumo` int(11) DEFAULT NULL,
  `id_suministro` int(11) DEFAULT NULL,
  `cantidad` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `entregas_items`
--

INSERT INTO `entregas_items` (`id`, `id_entrega`, `id_insumo`, `id_suministro`, `cantidad`) VALUES
(1, 1, 5, NULL, 20);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_ajustes`
--

CREATE TABLE `historial_ajustes` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo_origen` enum('insumo','suministro') NOT NULL,
  `id_item` int(11) NOT NULL,
  `stock_anterior` int(11) NOT NULL,
  `stock_nuevo` int(11) NOT NULL,
  `fecha_cambio` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_ajustes`
--

INSERT INTO `historial_ajustes` (`id`, `id_usuario`, `tipo_origen`, `id_item`, `stock_anterior`, `stock_nuevo`, `fecha_cambio`) VALUES
(1, 4, 'suministro', 1, 55, 50, '2025-12-05 17:54:36'),
(2, 1, 'insumo', 5, 5, 2, '2025-12-10 12:25:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumos_medicos`
--

CREATE TABLE `insumos_medicos` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `unidad_medida` varchar(50) DEFAULT 'unidades',
  `stock_actual` int(11) DEFAULT 0,
  `stock_minimo` int(11) DEFAULT 5,
  `fecha_vencimiento` date DEFAULT NULL,
  `lote` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `insumos_medicos`
--

INSERT INTO `insumos_medicos` (`id`, `codigo`, `nombre`, `descripcion`, `unidad_medida`, `stock_actual`, `stock_minimo`, `fecha_vencimiento`, `lote`, `updated_at`) VALUES
(1, 'MED-001', 'Paracetamol 500mg', 'Analgésico y antipirético', 'cajas', 150, 20, '2026-12-01', 'LOT-9988', '2025-12-05 16:49:41'),
(2, 'MED-002', 'Ibuprofeno 600mg', 'Antiinflamatorio no esteroideo', 'cajas', 80, 15, '2025-08-15', 'LOT-1122', '2025-12-05 16:49:41'),
(3, 'MED-003', 'Gasas Estériles 10x10', 'Sobres individuales', 'unidades', 495, 100, '2027-01-01', 'GAS-001', '2025-12-10 14:58:30'),
(4, 'MED-004', 'Jeringas 5ml', 'Sin aguja, descartables', 'unidades', 300, 50, '2028-05-20', 'JER-555', '2025-12-05 16:49:41'),
(5, 'MED-005', 'Agua Oxigenada 10vol', 'Frasco 250ml', 'litros', 2, 5, '2025-11-30', 'OXI-22', '2025-12-10 12:25:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL,
  `id_usuario_destino` int(11) DEFAULT NULL,
  `id_rol_destino` int(11) DEFAULT NULL,
  `mensaje` varchar(255) NOT NULL,
  `url_destino` varchar(255) DEFAULT NULL,
  `leida` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id`, `id_usuario_destino`, `id_rol_destino`, `mensaje`, `url_destino`, `leida`, `fecha_creacion`) VALUES
(1, NULL, 7, 'Nueva OC Médica #PRUEBA requiere su aprobación.', 'insumos_oc_ver.php?id=11', 1, '2025-12-05 17:13:47'),
(2, 3, NULL, '❌ Director Médico RECHAZÓ la OC #PRUEBA.', 'insumos_oc_ver.php?id=11', 1, '2025-12-05 17:15:05'),
(3, NULL, 7, 'Nueva OC Médica #prueba requiere su aprobación.', 'insumos_oc_ver.php?id=12', 1, '2025-12-05 17:16:06'),
(4, 3, NULL, '❌ Director Médico RECHAZÓ la OC #prueba.', 'insumos_oc_ver.php?id=12', 1, '2025-12-05 17:16:51'),
(5, 3, NULL, '❌ Director Médico RECHAZÓ la OC #prueba.', 'insumos_oc_ver.php?id=12', 0, '2025-12-05 17:21:35'),
(6, NULL, 3, 'Nueva OC Suministros #25/25 pendiente de revisión.', 'suministros_oc_ver.php?id=13', 0, '2025-12-05 17:39:14'),
(7, 3, NULL, '✅ Logística APROBÓ la OC Suministros #25/25.', 'suministros_oc_ver.php?id=13', 0, '2025-12-05 17:39:37'),
(8, NULL, 5, 'Logística autorizó carga. OC #25/25', 'suministros_recepcion.php?id=13', 0, '2025-12-05 17:39:37'),
(9, 6, NULL, '¡Tu cuenta ha sido aprobada! Ya puedes ingresar.', 'dashboard.php', 0, '2025-12-10 14:20:35'),
(10, 6, NULL, '¡Tu cuenta ha sido aprobada! Ya puedes ingresar.', 'dashboard.php', 0, '2025-12-10 14:51:05'),
(11, 6, NULL, '¡Tu cuenta ha sido aprobada! Ya puedes ingresar.', 'dashboard.php', 0, '2025-12-10 14:51:06'),
(12, 6, NULL, '¡Tu cuenta ha sido aprobada! Ya puedes ingresar.', 'dashboard.php', 0, '2025-12-10 14:51:06'),
(13, 6, NULL, '¡Tu cuenta ha sido aprobada! Ya puedes ingresar.', 'dashboard.php', 0, '2025-12-10 14:51:06'),
(14, 6, NULL, '¡Tu cuenta ha sido aprobada! Ya puedes ingresar.', 'dashboard.php', 0, '2025-12-10 14:51:06'),
(15, 6, NULL, '¡Tu cuenta ha sido aprobada! Ya puedes ingresar.', 'dashboard.php', 0, '2025-12-10 14:51:06'),
(16, 6, NULL, '¡Tu cuenta ha sido aprobada! Ya puedes ingresar.', 'dashboard.php', 0, '2025-12-10 14:51:07'),
(17, 6, NULL, '¡Tu cuenta ha sido aprobada! Ya puedes ingresar.', 'dashboard.php', 0, '2025-12-10 14:51:07'),
(18, 6, NULL, '¡Tu cuenta ha sido aprobada! Ya puedes ingresar.', 'dashboard.php', 0, '2025-12-10 14:51:07'),
(19, 6, NULL, '¡Tu cuenta ha sido aprobada! Ya puedes ingresar.', 'dashboard.php', 0, '2025-12-10 14:51:07'),
(20, 4, NULL, '¡Tu cuenta ha sido aprobada! Ya puedes ingresar.', 'dashboard.php', 0, '2025-12-10 14:51:18'),
(21, 7, NULL, '¡Tu cuenta ha sido aprobada! Ya puedes ingresar.', 'dashboard.php', 0, '2025-12-10 14:51:20'),
(22, 2, NULL, '¡Tu cuenta ha sido aprobada! Ya puedes ingresar.', 'dashboard.php', 0, '2025-12-10 14:51:22'),
(23, 3, NULL, '¡Tu cuenta ha sido aprobada! Ya puedes ingresar.', 'dashboard.php', 0, '2025-12-10 14:51:23'),
(24, NULL, 1, 'Nuevo registro: FEDERICO GONZALEZ (Laboratorio). Requiere validación.', 'admin_usuarios.php', 0, '2025-12-10 14:55:57'),
(25, 8, NULL, '¡Tu cuenta ha sido aprobada! Ya puedes ingresar.', 'dashboard.php', 0, '2025-12-10 14:56:16'),
(26, NULL, 7, 'Nuevo pedido de Insumos: Laboratorio', 'pedidos_revision_director.php?id=1', 0, '2025-12-10 14:57:33'),
(27, NULL, 4, 'Pedido aprobado por Director (ID #1). Listo para despachar.', 'pedidos_despacho.php?id=1', 0, '2025-12-10 14:58:03'),
(28, NULL, 4, 'Nueva Adquisición Solicitada: Laboratorio', 'bandeja_gestion_dinamica.php?id=2', 0, '2025-12-11 13:37:02'),
(29, NULL, 7, 'Solicitud #2 requiere tu revisión (Aprobación Director Médico)', 'bandeja_gestion_dinamica.php?id=2', 0, '2025-12-11 13:37:26'),
(30, NULL, 2, 'Solicitud #2 requiere tu revisión (Gestión de Compras)', 'bandeja_gestion_dinamica.php?id=2', 0, '2025-12-11 13:37:46'),
(31, NULL, 4, 'El Director aprobó el pedido #2. Vuelve a ti.', 'bandeja_gestion_dinamica.php?id=2', 0, '2025-12-11 13:37:46'),
(32, 8, NULL, '📢 OC Generada para Pedido #2. En espera de proveedor.', 'dashboard.php', 0, '2025-12-11 13:43:50'),
(33, NULL, 4, '📢 OC Generada para Pedido #2. En espera de proveedor.', NULL, 0, '2025-12-11 13:43:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ordenes_compra`
--

CREATE TABLE `ordenes_compra` (
  `id` int(11) NOT NULL,
  `numero_oc` varchar(50) NOT NULL,
  `tipo_origen` enum('insumos','suministros') NOT NULL,
  `id_usuario_creador` int(11) NOT NULL,
  `estado` enum('pendiente_logistica','aprobada_logistica','rechazada','recibida_parcial','recibida_total') DEFAULT 'pendiente_logistica',
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `fecha_aprobacion` datetime DEFAULT NULL,
  `id_usuario_aprobador` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ordenes_compra`
--

INSERT INTO `ordenes_compra` (`id`, `numero_oc`, `tipo_origen`, `id_usuario_creador`, `estado`, `fecha_creacion`, `fecha_aprobacion`, `id_usuario_aprobador`, `observaciones`) VALUES
(1, 'OC-MED-1001', 'insumos', 1, 'aprobada_logistica', '2025-12-05 16:49:41', '2025-12-05 16:49:41', 1, NULL),
(2, 'OC-MED-1002', 'insumos', 1, 'aprobada_logistica', '2025-12-05 16:49:41', '2025-12-05 16:49:41', 1, NULL),
(3, 'OC-MED-1003', 'insumos', 1, 'aprobada_logistica', '2025-12-05 16:49:41', '2025-12-05 16:49:41', 1, NULL),
(4, 'OC-MED-1004', 'insumos', 1, 'aprobada_logistica', '2025-12-05 16:49:41', '2025-12-05 16:49:41', 1, NULL),
(5, 'OC-MED-1005', 'insumos', 1, 'aprobada_logistica', '2025-12-05 16:49:41', '2025-12-05 16:49:41', 1, NULL),
(6, 'OC-SUM-2001', 'suministros', 1, 'aprobada_logistica', '2025-12-05 16:49:41', '2025-12-05 16:49:41', 1, NULL),
(7, 'OC-SUM-2002', 'suministros', 1, 'aprobada_logistica', '2025-12-05 16:49:41', '2025-12-05 16:49:41', 1, NULL),
(8, 'OC-SUM-2003', 'suministros', 1, 'aprobada_logistica', '2025-12-05 16:49:41', '2025-12-05 16:49:41', 1, NULL),
(9, 'OC-SUM-2004', 'suministros', 1, 'aprobada_logistica', '2025-12-05 16:49:41', '2025-12-05 16:49:41', 1, NULL),
(10, 'OC-SUM-2005', 'suministros', 1, 'aprobada_logistica', '2025-12-05 16:49:41', '2025-12-05 16:49:41', 1, NULL),
(11, 'PRUEBA', 'insumos', 3, 'rechazada', '2025-12-05 17:13:47', '2025-12-05 17:15:05', 6, ''),
(12, 'prueba', 'insumos', 3, 'rechazada', '2025-12-05 17:16:06', '2025-12-05 17:21:35', 6, ''),
(13, '25/25', 'suministros', 3, 'recibida_total', '2025-12-05 17:39:14', '2025-12-05 17:39:37', 2, 'xxxx\n[RECIBIDO] Fecha: 2025-12-05 17:40 - Remito: Uwwye - Por: SUMINISTROS');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ordenes_compra_items`
--

CREATE TABLE `ordenes_compra_items` (
  `id` int(11) NOT NULL,
  `id_oc` int(11) NOT NULL,
  `descripcion_producto` varchar(200) NOT NULL,
  `cantidad_solicitada` int(11) NOT NULL,
  `cantidad_recibida` int(11) DEFAULT 0,
  `precio_estimado` decimal(10,2) DEFAULT NULL,
  `id_insumo_asociado` int(11) DEFAULT NULL,
  `id_suministro_asociado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ordenes_compra_items`
--

INSERT INTO `ordenes_compra_items` (`id`, `id_oc`, `descripcion_producto`, `cantidad_solicitada`, `cantidad_recibida`, `precio_estimado`, `id_insumo_asociado`, `id_suministro_asociado`) VALUES
(1, 1, 'Paracetamol 500mg (Reposición)', 50, 0, 1500.00, 1, NULL),
(2, 2, 'Ibuprofeno Lote Nuevo', 30, 0, 2200.50, 2, NULL),
(3, 3, 'Gasas y Jeringas', 200, 0, 500.00, 3, NULL),
(4, 3, 'Jeringas 5ml', 100, 0, 350.00, 4, NULL),
(5, 4, 'Agua Oxigenada', 10, 0, 1200.00, 5, NULL),
(6, 5, 'Guantes Latex (Nuevo Item)', 100, 0, 800.00, NULL, NULL),
(7, 6, 'Resmas A4 Autoridad', 20, 0, 6500.00, NULL, 1),
(8, 7, 'Lavandina para pisos', 5, 0, 3000.00, NULL, 2),
(9, 8, 'Lapiceras y Toner', 2, 0, 4500.00, NULL, 3),
(10, 8, 'Tóner HP Reserva', 1, 0, 85000.00, NULL, 5),
(11, 9, 'Detergente Cocina', 10, 0, 2500.00, NULL, 4),
(12, 10, 'Café para oficina (Item Nuevo)', 5, 0, 9000.00, NULL, NULL),
(13, 11, 'PRUEBA', 12, 0, 2.00, NULL, NULL),
(14, 12, 'pruenaa', 1, 0, 2.00, NULL, NULL),
(15, 13, 'remas a4', 10, 10, 14000.00, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos_items`
--

CREATE TABLE `pedidos_items` (
  `id` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_insumo` int(11) DEFAULT NULL,
  `id_suministro` int(11) DEFAULT NULL,
  `cantidad_solicitada` int(11) NOT NULL,
  `cantidad_aprobada` int(11) DEFAULT NULL,
  `cantidad_entregada` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos_servicio`
--

CREATE TABLE `pedidos_servicio` (
  `id` int(11) NOT NULL,
  `tipo_insumo` enum('insumos_medicos','suministros') NOT NULL,
  `id_usuario_solicitante` int(11) NOT NULL,
  `servicio_solicitante` varchar(100) NOT NULL,
  `prioridad` enum('Normal','Urgente','Extraordinaria') DEFAULT NULL,
  `frecuencia_compra` enum('Mensual','Trimestral','Semestral','Anual') DEFAULT NULL,
  `fecha_solicitud` timestamp NULL DEFAULT current_timestamp(),
  `estado` enum('pendiente_director','aprobado_director','pendiente_logistica','aprobado_logistica','entregado','rechazado','finalizado_proceso','esperando_entrega') DEFAULT 'pendiente_director',
  `fecha_aprobacion_director` datetime DEFAULT NULL,
  `fecha_aprobacion_logistica` datetime DEFAULT NULL,
  `id_director_aprobador` int(11) DEFAULT NULL,
  `id_logistica_aprobador` int(11) DEFAULT NULL,
  `fecha_entrega_real` datetime DEFAULT NULL,
  `id_usuario_entrega` int(11) DEFAULT NULL,
  `observaciones_director` text DEFAULT NULL,
  `observaciones_logistica` text DEFAULT NULL,
  `observaciones_entrega` text DEFAULT NULL,
  `paso_actual_id` int(11) DEFAULT NULL,
  `proceso_origen` varchar(50) DEFAULT 'movimiento_insumos'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`, `descripcion`) VALUES
(1, 'Administrador', 'Control total del sistema y gestión de permisos'),
(2, 'Compras', 'Generación de Órdenes de Compra'),
(3, 'Encargado Logística', 'Aprobación de OC y supervisión'),
(4, 'Encargado Depósito Insumos', 'Recepción y gestión de Insumos Médicos'),
(5, 'Encargado Depósito Suministros', 'Recepción y gestión de Suministros Generales'),
(6, 'Auxiliar', 'Ayuda en gestión y entregas'),
(7, 'Director Médico', 'Autoriza Órdenes de Compra de Insumos Médicos'),
(8, 'Director Operativo', 'Aprueba adquisiciones de Suministros Generales');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol_permisos`
--

CREATE TABLE `rol_permisos` (
  `id_rol` int(11) NOT NULL,
  `id_permiso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suministros_generales`
--

CREATE TABLE `suministros_generales` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `unidad_medida` varchar(50) DEFAULT 'unidades',
  `stock_actual` int(11) DEFAULT 0,
  `stock_minimo` int(11) DEFAULT 5,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `suministros_generales`
--

INSERT INTO `suministros_generales` (`id`, `codigo`, `nombre`, `descripcion`, `unidad_medida`, `stock_actual`, `stock_minimo`, `updated_at`) VALUES
(1, 'OF-001', 'Resma A4 75g', 'Papel para impresora, marca líder', 'paquetes', 50, 10, '2025-12-05 17:54:36'),
(2, 'LIM-001', 'Lavandina Concentrada', 'Bidón de 5 Litros', 'litros', 10, 2, '2025-12-05 16:49:41'),
(3, 'OF-002', 'Bolígrafos Azules', 'Caja x 50 unidades', 'cajas', 5, 1, '2025-12-05 16:49:41'),
(4, 'LIM-002', 'Detergente Industrial', 'Desengrasante potente', 'litros', 20, 5, '2025-12-05 16:49:41'),
(5, 'OF-003', 'Tóner HP 85A', 'Cartucho original negro', 'unidades', 3, 1, '2025-12-05 16:49:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `destino` varchar(100) DEFAULT NULL,
  `servicio` varchar(100) DEFAULT NULL,
  `grado_militar` varchar(100) DEFAULT NULL,
  `rol_en_servicio` varchar(50) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `numero_interno` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `firma_digital` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `validado_por_admin` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre_completo`, `email`, `destino`, `servicio`, `grado_militar`, `rol_en_servicio`, `telefono`, `numero_interno`, `password`, `firma_digital`, `activo`, `validado_por_admin`, `created_at`) VALUES
(1, 'Super Admin', 'admin@actis.com', NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$hE1T2etv4shephi4qceDHe6nL97Sv0PjkyVl3nBF8hchjc7waakf2', 'uploads/firmas/firma_1_1764967110.png', 1, 1, '2025-12-05 12:01:01'),
(2, 'ENCARGADO LOGISTICA', 'logistica@actis.com', NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$pXLirllX5p2Yl6Yt0Af2b.lACBuHNHzDo5jwUmAWtZWR.pwednDOK', NULL, 1, 1, '2025-12-05 14:11:36'),
(3, 'COMPRAS', 'compras@actis.com', NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$dwL2x.fsz0PpxQ9gC5T9iOmLsEfjRBtCNoLo771d0IqD4x3aH.eDG', 'uploads/firmas/firma_user_3_1764946491.png', 1, 1, '2025-12-05 14:12:43'),
(4, 'SUMINISTROS', 'suministros@actis.com', NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$V96q4zZoyDehKNuZdZ7QuuJRtKEvy.cWZe/Waa7Zj8f4y8TLzEYOK', NULL, 1, 1, '2025-12-05 15:26:00'),
(6, 'DIRECTOR MEDICO', 'dirmedico@actis.com', NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$Ovphj6ZEmYVl06X8oxz8SujjrskHK22hlRBO.RWVt3O6ax2ZvPYie', 'uploads/firmas/firma_6_1764956086.png', 1, 1, '2025-12-05 16:35:22'),
(7, 'INSUMOS MEDICOS', 'insumos@actis.com', NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$zMueH2Ele6SSnakVo3mEa..Q0hL1XvR4qsqMgwBMANty89He5EElK', NULL, 1, 1, '2025-12-05 16:36:41'),
(8, 'FEDERICO GONZALEZ', 'gonzalezfedericomarcelo@gmail.com', 'ACTIS', 'Laboratorio', 'SG', 'Responsable', '1166116861', '', '$2y$10$Magq/sqMtpAjXUWkHDN9yuHaCukP5kxmT9ounkMBCedywkMelSDqO', NULL, 1, 1, '2025-12-10 14:55:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_roles`
--

CREATE TABLE `usuario_roles` (
  `id_usuario` int(11) NOT NULL,
  `id_rol` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario_roles`
--

INSERT INTO `usuario_roles` (`id_usuario`, `id_rol`) VALUES
(1, 1),
(3, 2),
(2, 3),
(7, 4),
(4, 5),
(6, 7);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `adjuntos`
--
ALTER TABLE `adjuntos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `config_flujos`
--
ALTER TABLE `config_flujos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `config_procesos`
--
ALTER TABLE `config_procesos`
  ADD PRIMARY KEY (`codigo`);

--
-- Indices de la tabla `entregas`
--
ALTER TABLE `entregas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario_responsable` (`id_usuario_responsable`);

--
-- Indices de la tabla `entregas_items`
--
ALTER TABLE `entregas_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_entrega` (`id_entrega`),
  ADD KEY `id_insumo` (`id_insumo`),
  ADD KEY `id_suministro` (`id_suministro`);

--
-- Indices de la tabla `historial_ajustes`
--
ALTER TABLE `historial_ajustes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `insumos_medicos`
--
ALTER TABLE `insumos_medicos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario_destino` (`id_usuario_destino`),
  ADD KEY `id_rol_destino` (`id_rol_destino`);

--
-- Indices de la tabla `ordenes_compra`
--
ALTER TABLE `ordenes_compra`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario_creador` (`id_usuario_creador`),
  ADD KEY `id_usuario_aprobador` (`id_usuario_aprobador`);

--
-- Indices de la tabla `ordenes_compra_items`
--
ALTER TABLE `ordenes_compra_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_oc` (`id_oc`),
  ADD KEY `id_insumo_asociado` (`id_insumo_asociado`),
  ADD KEY `id_suministro_asociado` (`id_suministro_asociado`);

--
-- Indices de la tabla `pedidos_items`
--
ALTER TABLE `pedidos_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pedido` (`id_pedido`);

--
-- Indices de la tabla `pedidos_servicio`
--
ALTER TABLE `pedidos_servicio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario_solicitante` (`id_usuario_solicitante`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `rol_permisos`
--
ALTER TABLE `rol_permisos`
  ADD PRIMARY KEY (`id_rol`,`id_permiso`),
  ADD KEY `id_permiso` (`id_permiso`);

--
-- Indices de la tabla `suministros_generales`
--
ALTER TABLE `suministros_generales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `usuario_roles`
--
ALTER TABLE `usuario_roles`
  ADD PRIMARY KEY (`id_usuario`,`id_rol`),
  ADD KEY `id_rol` (`id_rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `adjuntos`
--
ALTER TABLE `adjuntos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `config_flujos`
--
ALTER TABLE `config_flujos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `entregas`
--
ALTER TABLE `entregas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `entregas_items`
--
ALTER TABLE `entregas_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `historial_ajustes`
--
ALTER TABLE `historial_ajustes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `insumos_medicos`
--
ALTER TABLE `insumos_medicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de la tabla `ordenes_compra`
--
ALTER TABLE `ordenes_compra`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `ordenes_compra_items`
--
ALTER TABLE `ordenes_compra_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `pedidos_items`
--
ALTER TABLE `pedidos_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `pedidos_servicio`
--
ALTER TABLE `pedidos_servicio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `suministros_generales`
--
ALTER TABLE `suministros_generales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `entregas`
--
ALTER TABLE `entregas`
  ADD CONSTRAINT `entregas_ibfk_1` FOREIGN KEY (`id_usuario_responsable`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `entregas_items`
--
ALTER TABLE `entregas_items`
  ADD CONSTRAINT `entregas_items_ibfk_1` FOREIGN KEY (`id_entrega`) REFERENCES `entregas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `entregas_items_ibfk_2` FOREIGN KEY (`id_insumo`) REFERENCES `insumos_medicos` (`id`),
  ADD CONSTRAINT `entregas_items_ibfk_3` FOREIGN KEY (`id_suministro`) REFERENCES `suministros_generales` (`id`);

--
-- Filtros para la tabla `historial_ajustes`
--
ALTER TABLE `historial_ajustes`
  ADD CONSTRAINT `historial_ajustes_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`id_usuario_destino`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notificaciones_ibfk_2` FOREIGN KEY (`id_rol_destino`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ordenes_compra`
--
ALTER TABLE `ordenes_compra`
  ADD CONSTRAINT `ordenes_compra_ibfk_1` FOREIGN KEY (`id_usuario_creador`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `ordenes_compra_ibfk_2` FOREIGN KEY (`id_usuario_aprobador`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `ordenes_compra_items`
--
ALTER TABLE `ordenes_compra_items`
  ADD CONSTRAINT `ordenes_compra_items_ibfk_1` FOREIGN KEY (`id_oc`) REFERENCES `ordenes_compra` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ordenes_compra_items_ibfk_2` FOREIGN KEY (`id_insumo_asociado`) REFERENCES `insumos_medicos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ordenes_compra_items_ibfk_3` FOREIGN KEY (`id_suministro_asociado`) REFERENCES `suministros_generales` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `rol_permisos`
--
ALTER TABLE `rol_permisos`
  ADD CONSTRAINT `rol_permisos_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rol_permisos_ibfk_2` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuario_roles`
--
ALTER TABLE `usuario_roles`
  ADD CONSTRAINT `usuario_roles_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `usuario_roles_ibfk_2` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
