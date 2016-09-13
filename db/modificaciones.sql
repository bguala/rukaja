
ALTER TABLE aula ALTER COLUMN nombre TYPE character varying;
ALTER TABLE aula ALTER COLUMN ubicacion TYPE character varying;

ALTER TABLE organizacion ALTER COLUMN nombre TYPE character varying;

ALTER TABLE solicitud ALTER COLUMN nombre TYPE character varying;
ALTER TABLE solicitud ALTER COLUMN finalidad TYPE character varying;
ALTER TABLE solicitud ALTER COLUMN estado TYPE character varying;
ALTER TABLE solicitud ALTER COLUMN tipo_agente TYPE character varying;
ALTER TABLE solicitud ALTER COLUMN tipo_asignacion TYPE character varying;
ALTER TABLE solicitud ADD COLUMN tipo character varying(5);

ALTER TABLE tipo ALTER COLUMN descripcion TYPE character varying;

ALTER TABLE tipo_asignacion ALTER COLUMN tipo TYPE character varying;

ALTER TABLE asignacion ALTER COLUMN finalidad TYPE character varying;
ALTER TABLE asignacion ALTER COLUMN descripcion TYPE character varying;
ALTER TABLE asignacion ALTER COLUMN tipo_asignacion TYPE character varying;
