-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 10-12-2025 a las 10:45:03
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
  `entidad_tipo` enum('orden_compra','entrega','usuario') NOT NULL,
  `id_entidad` int(11) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `fecha_subida` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 4, 'suministro', 1, 55, 50, '2025-12-05 17:54:36');

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
(3, 'MED-003', 'Gasas Estériles 10x10', 'Sobres individuales', 'unidades', 500, 100, '2027-01-01', 'GAS-001', '2025-12-05 16:49:41'),
(4, 'MED-004', 'Jeringas 5ml', 'Sin aguja, descartables', 'unidades', 300, 50, '2028-05-20', 'JER-555', '2025-12-05 16:49:41'),
(5, 'MED-005', 'Agua Oxigenada 10vol', 'Frasco 250ml', 'litros', 25, 5, '2025-11-30', 'OXI-22', '2025-12-05 16:49:41');

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
(8, NULL, 5, 'Logística autorizó carga. OC #25/25', 'suministros_recepcion.php?id=13', 0, '2025-12-05 17:39:37');

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
(7, 'Director Médico', 'Autoriza Órdenes de Compra de Insumos Médicos');

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
  `password` varchar(255) NOT NULL,
  `firma_digital` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre_completo`, `email`, `password`, `firma_digital`, `activo`, `created_at`) VALUES
(1, 'Super Admin', 'admin@actis.com', '$2y$10$hE1T2etv4shephi4qceDHe6nL97Sv0PjkyVl3nBF8hchjc7waakf2', 'uploads/firmas/firma_1_1764967110.png', 1, '2025-12-05 12:01:01'),
(2, 'ENCARGADO LOGISTICA', 'logistica@actis.com', '$2y$10$pXLirllX5p2Yl6Yt0Af2b.lACBuHNHzDo5jwUmAWtZWR.pwednDOK', NULL, 1, '2025-12-05 14:11:36'),
(3, 'COMPRAS', 'compras@actis.com', '$2y$10$dwL2x.fsz0PpxQ9gC5T9iOmLsEfjRBtCNoLo771d0IqD4x3aH.eDG', 'uploads/firmas/firma_user_3_1764946491.png', 1, '2025-12-05 14:12:43'),
(4, 'SUMINISTROS', 'suministros@actis.com', '$2y$10$V96q4zZoyDehKNuZdZ7QuuJRtKEvy.cWZe/Waa7Zj8f4y8TLzEYOK', NULL, 1, '2025-12-05 15:26:00'),
(6, 'DIRECTOR MEDICO', 'dirmedico@actis.com', '$2y$10$Ovphj6ZEmYVl06X8oxz8SujjrskHK22hlRBO.RWVt3O6ax2ZvPYie', 'uploads/firmas/firma_6_1764956086.png', 1, '2025-12-05 16:35:22'),
(7, 'INSUMOS MEDICOS', 'insumos@actis.com', '$2y$10$zMueH2Ele6SSnakVo3mEa..Q0hL1XvR4qsqMgwBMANty89He5EElK', NULL, 1, '2025-12-05 16:36:41');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `entregas`
--
ALTER TABLE `entregas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `entregas_items`
--
ALTER TABLE `entregas_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_ajustes`
--
ALTER TABLE `historial_ajustes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `insumos_medicos`
--
ALTER TABLE `insumos_medicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `suministros_generales`
--
ALTER TABLE `suministros_generales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
