------------------------------------------------------------
--[2847]--  DT - docente 
------------------------------------------------------------

------------------------------------------------------------
-- apex_objeto
------------------------------------------------------------

--- INICIO Grupo de desarrollo 0
INSERT INTO apex_objeto (proyecto, objeto, anterior, identificador, reflexivo, clase_proyecto, clase, punto_montaje, subclase, subclase_archivo, objeto_categoria_proyecto, objeto_categoria, nombre, titulo, colapsable, descripcion, fuente_datos_proyecto, fuente_datos, solicitud_registrar, solicitud_obj_obs_tipo, solicitud_obj_observacion, parametro_a, parametro_b, parametro_c, parametro_d, parametro_e, parametro_f, usuario, creacion, posicion_botonera) VALUES (
	'rukaja', --proyecto
	'2847', --objeto
	NULL, --anterior
	NULL, --identificador
	NULL, --reflexivo
	'toba', --clase_proyecto
	'toba_datos_tabla', --clase
	'21', --punto_montaje
	NULL, --subclase
	NULL, --subclase_archivo
	NULL, --objeto_categoria_proyecto
	NULL, --objeto_categoria
	'DT - docente', --nombre
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
	'2016-05-08 17:43:16', --creacion
	NULL  --posicion_botonera
);
--- FIN Grupo de desarrollo 0

------------------------------------------------------------
-- apex_objeto_db_registros
------------------------------------------------------------
INSERT INTO apex_objeto_db_registros (objeto_proyecto, objeto, max_registros, min_registros, punto_montaje, ap, ap_clase, ap_archivo, tabla, tabla_ext, alias, modificar_claves, fuente_datos_proyecto, fuente_datos, permite_actualizacion_automatica, esquema, esquema_ext) VALUES (
	'rukaja', --objeto_proyecto
	'2847', --objeto
	NULL, --max_registros
	NULL, --min_registros
	'21', --punto_montaje
	'1', --ap
	NULL, --ap_clase
	NULL, --ap_archivo
	'docente', --tabla
	NULL, --tabla_ext
	NULL, --alias
	'0', --modificar_claves
	'rukaja', --fuente_datos_proyecto
	'rukaja', --fuente_datos
	'1', --permite_actualizacion_automatica
	'public', --esquema
	'public'  --esquema_ext
);

------------------------------------------------------------
-- apex_objeto_db_registros_col
------------------------------------------------------------

--- INICIO Grupo de desarrollo 0
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'rukaja', --objeto_proyecto
	'2847', --objeto
	'1111', --col_id
	'nro_doc', --columna
	'C', --tipo
	'1', --pk
	'', --secuencia
	'20', --largo
	NULL, --no_nulo
	'1', --no_nulo_db
	NULL, --externa
	'docente'  --tabla
);
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'rukaja', --objeto_proyecto
	'2847', --objeto
	'1112', --col_id
	'tipo_doc', --columna
	'C', --tipo
	'1', --pk
	'', --secuencia
	'12', --largo
	NULL, --no_nulo
	'1', --no_nulo_db
	NULL, --externa
	'docente'  --tabla
);
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'rukaja', --objeto_proyecto
	'2847', --objeto
	'1113', --col_id
	'titulo', --columna
	'C', --tipo
	'0', --pk
	'', --secuencia
	'70', --largo
	NULL, --no_nulo
	'0', --no_nulo_db
	NULL, --externa
	'docente'  --tabla
);
INSERT INTO apex_objeto_db_registros_col (objeto_proyecto, objeto, col_id, columna, tipo, pk, secuencia, largo, no_nulo, no_nulo_db, externa, tabla) VALUES (
	'rukaja', --objeto_proyecto
	'2847', --objeto
	'1114', --col_id
	'legajo', --columna
	'C', --tipo
	'0', --pk
	'', --secuencia
	'20', --largo
	NULL, --no_nulo
	'0', --no_nulo_db
	NULL, --externa
	'docente'  --tabla
);
--- FIN Grupo de desarrollo 0
