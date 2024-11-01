-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 01-11-2024 a las 01:23:39
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `ecommerce`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carrito`
--

CREATE TABLE `carrito` (
  `ID_Carrito` int(11) NOT NULL,
  `fecha_modificacion` datetime DEFAULT current_timestamp(),
  `ID_Usuario` int(11) DEFAULT NULL,
  `estado` enum('pendiente','procesando','completado','cancelado') DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `carrito`
--

INSERT INTO `carrito` (`ID_Carrito`, `fecha_modificacion`, `ID_Usuario`, `estado`) VALUES
(1, '2024-10-23 00:55:04', 3, ''),
(40, '2024-10-23 18:40:12', 3, ''),
(41, '2024-10-23 18:40:13', 3, ''),
(42, '2024-10-30 19:25:04', 3, 'pendiente'),
(43, '2024-10-28 17:35:13', 5, ''),
(45, '2024-10-28 17:35:15', 5, ''),
(46, '2024-10-29 17:20:11', 5, 'pendiente'),
(47, '2024-10-29 00:44:12', 6, 'pendiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carrito_producto`
--

CREATE TABLE `carrito_producto` (
  `ID_Carrito` int(11) NOT NULL,
  `ID_Producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `fecha_agregado` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `carrito_producto`
--

INSERT INTO `carrito_producto` (`ID_Carrito`, `ID_Producto`, `cantidad`, `fecha_agregado`) VALUES
(42, 1, 1, '2024-10-24 17:30:32'),
(42, 4, 15, '2024-10-28 17:52:15'),
(42, 5, 1, '2024-10-30 19:25:04'),
(43, 3, 1, '2024-10-28 17:35:13'),
(45, 4, 1, '2024-10-28 17:35:15'),
(46, 2, 1, '2024-10-29 00:26:56'),
(46, 3, 1, '2024-10-29 17:20:10'),
(46, 4, 1, '2024-10-29 17:20:09'),
(46, 5, 1, '2024-10-29 17:20:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chatconversacion`
--

CREATE TABLE `chatconversacion` (
  `ID_Conversacion` int(11) NOT NULL,
  `ID_Usuario` int(11) DEFAULT NULL,
  `fecha_inicio` datetime DEFAULT current_timestamp(),
  `fecha_fin` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chatestadisticas`
--

CREATE TABLE `chatestadisticas` (
  `ID_Estadistica` int(11) NOT NULL,
  `fecha` date DEFAULT NULL,
  `total_conversaciones` int(11) DEFAULT NULL,
  `promedio_duracion_conversacion` float DEFAULT NULL,
  `top_categoria_preguntas` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chatmensaje`
--

CREATE TABLE `chatmensaje` (
  `ID_Mensaje` int(11) NOT NULL,
  `ID_Conversacion` int(11) DEFAULT NULL,
  `contenido` text NOT NULL,
  `es_bot` tinyint(1) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `envio`
--

CREATE TABLE `envio` (
  `ID_Envio` int(11) NOT NULL,
  `empresa_envio` varchar(255) DEFAULT NULL,
  `numero_seguimiento` varchar(255) DEFAULT NULL,
  `costo_envio` decimal(10,2) DEFAULT NULL,
  `fecha_envio` datetime DEFAULT current_timestamp(),
  `fecha_entrega` datetime DEFAULT NULL,
  `ID_Verificacion` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `faq`
--

CREATE TABLE `faq` (
  `ID_FAQ` int(11) NOT NULL,
  `pregunta` text NOT NULL,
  `respuesta` text NOT NULL,
  `categoria` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `feedback`
--

CREATE TABLE `feedback` (
  `ID_Feedback` int(11) NOT NULL,
  `ID_Usuario` int(11) DEFAULT NULL,
  `comentario` text NOT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pago`
--

CREATE TABLE `pago` (
  `ID_Pago` int(11) NOT NULL,
  `monto` int(11) NOT NULL,
  `fecha_pago` datetime DEFAULT current_timestamp(),
  `metodo_pago` varchar(100) DEFAULT NULL,
  `estado_pago` varchar(50) DEFAULT NULL,
  `ID_Pedido` int(11) DEFAULT NULL,
  `ID_Usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expiracion` datetime NOT NULL,
  `usado` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido`
--

CREATE TABLE `pedido` (
  `ID_Pedido` int(11) NOT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `fecha_compra` datetime DEFAULT current_timestamp(),
  `estado` varchar(50) DEFAULT NULL,
  `metodo_pago` varchar(100) DEFAULT NULL,
  `direccion_envio` text DEFAULT NULL,
  `ID_Usuario` int(11) DEFAULT NULL,
  `ID_Carrito` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido_producto`
--

CREATE TABLE `pedido_producto` (
  `ID_Pedido` int(11) NOT NULL,
  `ID_Producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `ID_Producto` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `Precio` int(7) NOT NULL,
  `Cantidad` int(11) NOT NULL,
  `categoria` varchar(255) DEFAULT NULL,
  `imagen_producto` varchar(255) DEFAULT NULL,
  `valoracion` decimal(2,1) DEFAULT NULL,
  `ID_Vendedor` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`ID_Producto`, `nombre`, `descripcion`, `Precio`, `Cantidad`, `categoria`, `imagen_producto`, `valoracion`, `ID_Vendedor`) VALUES
(1, 'Platano', 'Delicioso platano amarillo, en remate por ser el ultimo lote del stock', 1500, 20, 'Fruta', 'uploads/67144db5bf1af.jpg', NULL, 4),
(2, 'Platano', 'Delicioso platano amarillo, en remate por ser el ultimo lote del stock', 1500, 20, 'Fruta', 'uploads/67144e2679702.jpg', NULL, 4),
(3, 'Papa', 'verdura color café en excelente estado, se remata debido a ultimo stock del pedido', 2000, 40, 'Verdura', 'uploads/6716b664ea1c1.jpg', NULL, 4),
(4, 'Palta', 'para muchos considerado oro verde, es uno de los productos más populares en el mercado latinoamerica, con un sabor cremoso y exquisito', 2200, 15, 'Verdura', 'uploads/6716d6c9c861c.jpg', NULL, 4),
(5, 'Pepino', 'El pepino es una hortaliza de verano, de forma alargada y de unos 15cm de largo. Su piel es de color verde que se aclara hasta volverse amarilla en la madurez', 800, 5, 'Verdura', 'uploads/6716df0db7cde.jpg', NULL, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reseña`
--

CREATE TABLE `reseña` (
  `ID_Revision` int(11) NOT NULL,
  `calificacion` decimal(2,1) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `ID_Producto` int(11) DEFAULT NULL,
  `ID_Usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `ID_Usuario` int(11) NOT NULL,
  `Nombre` varchar(255) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `Correo_electronico` varchar(255) NOT NULL,
  `Contacto` varchar(255) DEFAULT NULL,
  `Direccion` text DEFAULT NULL,
  `Historial_compras` text DEFAULT NULL,
  `Fecha_registro` datetime DEFAULT current_timestamp(),
  `imagen_usuario` varchar(255) DEFAULT NULL,
  `comuna` varchar(255) DEFAULT NULL,
  `rol` int(11) NOT NULL DEFAULT 0,
  `preferencias_chat` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferencias_chat`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`ID_Usuario`, `Nombre`, `contrasena`, `Correo_electronico`, `Contacto`, `Direccion`, `Historial_compras`, `Fecha_registro`, `imagen_usuario`, `comuna`, `rol`, `preferencias_chat`) VALUES
(3, 'test', '$2y$10$ok5i2pdOHlqn5hF07ZDPGOtBETn6JB9061CQgihl95ayRR9yLAtv2', 'test@gmail.com', '1566161', 'Bravo Seravia', NULL, '2024-10-17 21:33:12', NULL, 'Renca', 1, NULL),
(4, 'test2', '$2y$10$6HVcTJbc.XdxsxJLIU.I9uKALqGAdp6KjImUtEOEMMCGOmI1cuPES', 'test2@gmail.com', '6561616', 'Bravo Seravia', NULL, '2024-10-17 21:40:06', 'uploads/profile_images/4_1729267564.png', 'Renca', 0, NULL),
(5, 'Valeria Lopez', '$2y$10$TaawVLPCTJ5pRfn3.Z.3mOhPL0ya/IqR4zT7YLI6tLH.9WWfjgCHK', 'javiercito@gmail.com', '+56912345678', 'Bravo Seravia', NULL, '2024-10-28 17:33:58', NULL, 'Renca', 1, NULL),
(6, 'Brandon Marin', '$2y$10$dZ5pwgYqAZzaOsfmBf08VeAaPacJBdWNpukAwEhcyuDzWSGashlIK', 'Herrera3f@gmail.com', '+56911122234', 'Bravo Seravia', NULL, '2024-10-29 00:39:14', NULL, 'Pudahuel', 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `verificacion_pago`
--

CREATE TABLE `verificacion_pago` (
  `ID_Verificacion` int(11) NOT NULL,
  `estado_verificacion` varchar(100) DEFAULT NULL,
  `pago_realizado` tinyint(1) DEFAULT NULL,
  `ID_Pago` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD PRIMARY KEY (`ID_Carrito`),
  ADD KEY `ID_Usuario` (`ID_Usuario`);

--
-- Indices de la tabla `carrito_producto`
--
ALTER TABLE `carrito_producto`
  ADD PRIMARY KEY (`ID_Carrito`,`ID_Producto`),
  ADD KEY `ID_Producto` (`ID_Producto`);

--
-- Indices de la tabla `chatconversacion`
--
ALTER TABLE `chatconversacion`
  ADD PRIMARY KEY (`ID_Conversacion`),
  ADD KEY `idx_chat_conversacion_usuario` (`ID_Usuario`);

--
-- Indices de la tabla `chatestadisticas`
--
ALTER TABLE `chatestadisticas`
  ADD PRIMARY KEY (`ID_Estadistica`);

--
-- Indices de la tabla `chatmensaje`
--
ALTER TABLE `chatmensaje`
  ADD PRIMARY KEY (`ID_Mensaje`),
  ADD KEY `idx_chat_mensaje_conversacion` (`ID_Conversacion`);

--
-- Indices de la tabla `envio`
--
ALTER TABLE `envio`
  ADD PRIMARY KEY (`ID_Envio`),
  ADD KEY `ID_Verificacion` (`ID_Verificacion`);

--
-- Indices de la tabla `faq`
--
ALTER TABLE `faq`
  ADD PRIMARY KEY (`ID_FAQ`);

--
-- Indices de la tabla `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`ID_Feedback`),
  ADD KEY `ID_Usuario` (`ID_Usuario`);

--
-- Indices de la tabla `pago`
--
ALTER TABLE `pago`
  ADD PRIMARY KEY (`ID_Pago`),
  ADD KEY `ID_Pedido` (`ID_Pedido`),
  ADD KEY `ID_Usuario` (`ID_Usuario`);

--
-- Indices de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_email` (`email`);

--
-- Indices de la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD PRIMARY KEY (`ID_Pedido`),
  ADD KEY `ID_Carrito` (`ID_Carrito`),
  ADD KEY `idx_pedido_usuario` (`ID_Usuario`);

--
-- Indices de la tabla `pedido_producto`
--
ALTER TABLE `pedido_producto`
  ADD PRIMARY KEY (`ID_Pedido`,`ID_Producto`),
  ADD KEY `ID_Producto` (`ID_Producto`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`ID_Producto`),
  ADD KEY `ID_Vendedor` (`ID_Vendedor`),
  ADD KEY `idx_producto_nombre` (`nombre`);

--
-- Indices de la tabla `reseña`
--
ALTER TABLE `reseña`
  ADD PRIMARY KEY (`ID_Revision`),
  ADD KEY `ID_Producto` (`ID_Producto`),
  ADD KEY `ID_Usuario` (`ID_Usuario`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`ID_Usuario`);

--
-- Indices de la tabla `verificacion_pago`
--
ALTER TABLE `verificacion_pago`
  ADD PRIMARY KEY (`ID_Verificacion`),
  ADD KEY `ID_Pago` (`ID_Pago`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `carrito`
--
ALTER TABLE `carrito`
  MODIFY `ID_Carrito` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT de la tabla `chatconversacion`
--
ALTER TABLE `chatconversacion`
  MODIFY `ID_Conversacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `chatestadisticas`
--
ALTER TABLE `chatestadisticas`
  MODIFY `ID_Estadistica` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `chatmensaje`
--
ALTER TABLE `chatmensaje`
  MODIFY `ID_Mensaje` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `envio`
--
ALTER TABLE `envio`
  MODIFY `ID_Envio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `faq`
--
ALTER TABLE `faq`
  MODIFY `ID_FAQ` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `feedback`
--
ALTER TABLE `feedback`
  MODIFY `ID_Feedback` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pago`
--
ALTER TABLE `pago`
  MODIFY `ID_Pago` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedido`
--
ALTER TABLE `pedido`
  MODIFY `ID_Pedido` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `ID_Producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `reseña`
--
ALTER TABLE `reseña`
  MODIFY `ID_Revision` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `ID_Usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `verificacion_pago`
--
ALTER TABLE `verificacion_pago`
  MODIFY `ID_Verificacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD CONSTRAINT `carrito_ibfk_1` FOREIGN KEY (`ID_Usuario`) REFERENCES `usuario` (`ID_Usuario`);

--
-- Filtros para la tabla `carrito_producto`
--
ALTER TABLE `carrito_producto`
  ADD CONSTRAINT `carrito_producto_ibfk_1` FOREIGN KEY (`ID_Carrito`) REFERENCES `carrito` (`ID_Carrito`),
  ADD CONSTRAINT `carrito_producto_ibfk_2` FOREIGN KEY (`ID_Producto`) REFERENCES `producto` (`ID_Producto`);

--
-- Filtros para la tabla `chatconversacion`
--
ALTER TABLE `chatconversacion`
  ADD CONSTRAINT `chatconversacion_ibfk_1` FOREIGN KEY (`ID_Usuario`) REFERENCES `usuario` (`ID_Usuario`);

--
-- Filtros para la tabla `chatmensaje`
--
ALTER TABLE `chatmensaje`
  ADD CONSTRAINT `chatmensaje_ibfk_1` FOREIGN KEY (`ID_Conversacion`) REFERENCES `chatconversacion` (`ID_Conversacion`);

--
-- Filtros para la tabla `envio`
--
ALTER TABLE `envio`
  ADD CONSTRAINT `envio_ibfk_1` FOREIGN KEY (`ID_Verificacion`) REFERENCES `verificacion_pago` (`ID_Verificacion`);

--
-- Filtros para la tabla `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`ID_Usuario`) REFERENCES `usuario` (`ID_Usuario`);

--
-- Filtros para la tabla `pago`
--
ALTER TABLE `pago`
  ADD CONSTRAINT `pago_ibfk_1` FOREIGN KEY (`ID_Pedido`) REFERENCES `pedido` (`ID_Pedido`),
  ADD CONSTRAINT `pago_ibfk_2` FOREIGN KEY (`ID_Usuario`) REFERENCES `usuario` (`ID_Usuario`);

--
-- Filtros para la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD CONSTRAINT `pedido_ibfk_1` FOREIGN KEY (`ID_Usuario`) REFERENCES `usuario` (`ID_Usuario`),
  ADD CONSTRAINT `pedido_ibfk_2` FOREIGN KEY (`ID_Carrito`) REFERENCES `carrito` (`ID_Carrito`);

--
-- Filtros para la tabla `pedido_producto`
--
ALTER TABLE `pedido_producto`
  ADD CONSTRAINT `pedido_producto_ibfk_1` FOREIGN KEY (`ID_Pedido`) REFERENCES `pedido` (`ID_Pedido`),
  ADD CONSTRAINT `pedido_producto_ibfk_2` FOREIGN KEY (`ID_Producto`) REFERENCES `producto` (`ID_Producto`);

--
-- Filtros para la tabla `producto`
--
ALTER TABLE `producto`
  ADD CONSTRAINT `producto_ibfk_1` FOREIGN KEY (`ID_Vendedor`) REFERENCES `usuario` (`ID_Usuario`);

--
-- Filtros para la tabla `reseña`
--
ALTER TABLE `reseña`
  ADD CONSTRAINT `reseña_ibfk_1` FOREIGN KEY (`ID_Producto`) REFERENCES `producto` (`ID_Producto`),
  ADD CONSTRAINT `reseña_ibfk_2` FOREIGN KEY (`ID_Usuario`) REFERENCES `usuario` (`ID_Usuario`);

--
-- Filtros para la tabla `verificacion_pago`
--
ALTER TABLE `verificacion_pago`
  ADD CONSTRAINT `verificacion_pago_ibfk_1` FOREIGN KEY (`ID_Pago`) REFERENCES `pago` (`ID_Pago`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
