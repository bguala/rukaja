------------------------------------------------------------
--[2815]--  DT - asignacion 
------------------------------------------------------------

------------------------------------------------------------
-- apex_objeto
------------------------------------------------------------

--- INICIO Grupo de desarrollo 0
INSERT INTO apex_objeto (proyecto, objeto, anterior, identificador, reflexivo, clase_proyecto, clase, punto_montaje, subclase, subclase_archivo, objeto_categoria_proyecto, objeto_categoria, nombre, titulo, colapsable, descripcion, fuente_datos_proyecto, fuente_datos, solicitud_registrar, solicitud_obj_obs_tipo, solicitud_obj_observacion, parametro_a, parametro_b, parametro_c, parametro_d, parametro_e, parametro_f, usuario, creacion, posicion_botonera) VALUES (
	'rukaja', --proyecto
	'2815', --objeto
	NULL, --anterior
	NULL, --identificador
	NULL, --reflexivo
	'toba', --clase_proyecto
	'toba_datos_tabla', --clase
	'21', --punto_montaje
	'dt_asignacion', --subclase
	'datos/dt_asignacion.php', --subclase_archivo
	NULL, --objeto_categoria_proyecto
	NULL, --objeto_categoria
	'DT - asignacion', --nombre
	NULL, --titulo
	NULL, --colapsable
	NULL, --descripcion
	'rukaja', --fuente_datos_proyecto
	'rukaja', --fuente_datos
	NULL, --solicitud_registrar
	NULL, --solicitud_obj_obs_tipo
	NULL, --solicitud_obj_observacion
	NULL, --parametro_a
	NULL, --parametro_b
	NULL, --parametro_c
	NULL, --parametro_d
	NULL, --parametro_e
	NULL, --parametro_f
	NULL, --usuario
	'2016-05-08 00:33:56', --creacion
	NULL  --posicion_botonera
);
--- FIN Grupo de desarrollo 0

------------------------------------------------------------
-- apex_objeto_db_registros
------------------------------------------------------------
INSERT INTO apex_objeto_db_registros (objeto_proyecto, objeto, max_registros, min_registros, punto_montaje, ap, ap_clase, ap_archivo, tabla, tabla_ext, alias, modificar_claves, fuente_datos_proyecto, fuente_datos, permite_actualizacion_automatica, esquema, esquema_ext) VALUES (
	'rukaja', --objeto_proyecto
	'2815', --objeto
	NULL, --max_registros
	NULL, --min_registros
	'21', --punto_montaje
	'1', --ap
	NULL, --ap_clase
	NULL, --ap_archivo
	'asignacion', --tabla
	NULL, --tabla_ext
	NULL, --alias
	'0', --modificar_claves
	'rukaja', --fuente_datos_proyecto
	'rukaja', --fuente_datos
	'1', --permite_actualizacion_automatica
	NULL, --esquema
	'public'  --esquema_ext
);

------------------------------------------------------------
-- apex_objeto_db_registros_col
------------------------------------------------------------

--- INICIO Grupo de desarrollo 0
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'rukaja', --objeto_proyecto
	'2815', --objeto
	'1018', --col_id
	'id_asignacion', --columna
	'E', --tipo
	'1', --pk
	'asignacion_id_asignacion_seq', --secuencia
	NULL, --largo
	NULL, --no_nulo
	'1', --no_nulo_db
	NULL, --externa
	'asignacion'  --tabla
);
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'rukaja', --objeto_proyecto
	'2815', --objeto
	'1019', --col_id
	'finalidad', --columna
	'C', --tipo
	'0', --pk
	'', --secuencia
	'100', --largo
	NULL, --no_nulo
	'0', --no_nulo_db
	NULL, --externa
	'asignacion'  --tabla
);
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'rukaja', --objeto_proyecto
	'2815', --objeto
	'1020', --col_id
	'descripcion', --columna
	'C', --tipo
	'0', --pk
	'', --secuencia
	'250', --largo
	NULL, --no_nulo
	'0', --no_nulo_db
	NULL, --externa
	'asignacion'  --tabla
);
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'rukaja', --objeto_proyecto
	'2815', --objeto
	'1021', --col_id
	'hora_inicio', --columna
	'T', --tipo
	'0', --pk
	'', --secuencia
	NULL, --largo
	NULL, --no_nulo
	'0', --no_nulo_db
	NULL, --externa
	'asignacion'  --tabla
);
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'rukaja', --objeto_proyecto
	'2815', --objeto
	'1022', --col_id
	'hora_fin', --columna
	'T', --tipo
	'0', --pk
	'', --secuencia
	NULL, --largo
	NULL, --no_nulo
	'0', --no_nulo_db
	NULL, --externa
	'asignacion'  --tabla
);
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'rukaja', --objeto_proyecto
	'2815', --objeto
	'1023', --col_id
	'cantidad_alumnos', --columna
	'E', --tipo
	'0', --pk
	'', --secuencia
	NULL, --largo
	NULL, --no_nulo
	'0', --no_nulo_db
	NULL, --externa
	'asignacion'  --tabla
);
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'rukaja', --objeto_proyecto
	'2815', --objeto
	'1024', --col_id
	'facultad', --columna
	'C', --tipo
	'0', --pk
	'', --secuencia
	'6', --largo
	NULL, --no_nulo
	'0', --no_nulo_db
	NULL, --externa
	'asignacion'  --tabla
);
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'rukaja', --objeto_proyecto
	'2815', --objeto
	'1025', --col_id
	'nro_doc', --columna
	'C', --tipo
	'0', --pk
	'', --secuencia
	'20', --largo
	NULL, --no_nulo
	'0', --no_nulo_db
	NULL, --externa
	'asignacion'  --tabla
);
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'rukaja', --objeto_proyecto
	'2815', --objeto
	'1026', --col_id
	'tipo_doc', --columna
	'C', --tipo
	'0', --pk
	'', --secuencia
	'12', --largo
	NULL, --no_nulo
	'0', --no_nulo_db
	NULL, --externa
	'asignacion'  --tabla
);
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'rukaja', --objeto_proyecto
	'2815', --objeto
	'1027', --col_id
	'id_aula', --columna
	'E', --tipo
	'0', --pk
	'', --secuencia
	NULL, --largo
	NULL, --no_nulo
	'0', --no_nulo_db
	NULL, --externa
	'asignacion'  --tabla
);
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'rukaja', --objeto_proyecto
	'2815', --objeto
	'1028', --col_id
	'modulo', --columna
	'E', --tipo
	'0', --pk
	'', --secuencia
	NULL, --largo
	NULL, --no_nulo
	'0', --no_nulo_db
	NULL, --externa
	'asignacion'  --tabla
);
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'rukaja', --objeto_proyecto
	'2815', --objeto
	'1029', --col_id
	'tipo_asignacion', --columna
	'C', --tipo
	'0', --pk
	'', --secuencia
	'15', --largo
	NULL, --no_nulo
	'0', --no_nulo_db
	NULL, --externa
	'asignacion'  --tabla
);
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'rukaja', --objeto_proyecto
	'2815', --objeto
	'1030', --col_id
	'id_periodo', --columna
	'E', --tipo
	'0', --pk
	'', --secuencia
	NULL, --largo
	NULL, --no_nulo
	'0', --no_nulo_db
	NULL, --externa
	'asignacion'  --tabla
);
--- FIN Grupo de desarrollo 0
