
CREATE TABLE incidencia (

id_incidencia serial NOT NULL,
fecha date,
hora time without time zone,
id_aula integer,
id_sede integer,
estado character varying, --Pendiente, iniciada o finalizada.

CONSTRAINT pk_incidencia PRIMARY KEY (id_incidencia)

);

--Relacion entre Aula e Incidencia.
CREATE TABLE seguimiento (

id_aula serial NOT NULL,
id_incidencia serial NOT NULL,
fecha_seguimiento date,
hora_seguimiento date,
estado character varying,
tipo character varying,
nombre character varying,

CONSTRAINT pk_seguimiento PRIMARY KEY (id_aula, fecha_seguimiento, hora_seguimiento, id_incidencia),
CONSTRAINT fk_seg_aula FOREIGN KEY (id_aula) REFERENCES aula(id_aula),
CONSTRAINT fk_seg_incidencia FOREIGN KEY (id_incidencia) REFERENCES incidencia(id_incidencia)

);

CREATE TABLE material (

tipo character varying NOT NULL,
nombre character varying NOT NULL,

CONSTRAINT pk_material PRIMARY KEY (tipo, nombre)

);

INSERT INTO material (tipo, nombre) VALUES 
('ILUMINACION', 'CONDUCTOR'),
('ILUMINACION', 'TUBO FLUORESCENTE'),
('ILUMINACION', 'INTERRUPTOR'),
('ILUMINACION', 'TOMACORRIENTE'),
('OTRO', 'PUERTA SIN CERRADURA'),
('OTRO', 'AULA SIN ESCRITORIO'),
('OTRO', 'FALTA DE PUPITRES'),
('CALEFACCION', 'CALEFACTOR CON PERDIDA DE GAS'),
('CALEFACCION', 'CALEFACTOR SIN FUNCIONAMIENTO');

--Entidad debil entre Material e Incidencia.
CREATE TABLE cantidad_material (

cantidad integer,
descripcion character varying,
id_incidencia serial NOT NULL,
tipo character varying NOT NULL,
nombre character varying NOT NULL,

CONSTRAINT pk_cantidad_material PRIMARY KEY (id_incidencia, tipo, nombre),
CONSTRAINT fk_cantidad_material_incidencia FOREIGN KEY (id_incidencia)
      REFERENCES incidencia (id_incidencia) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
CONSTRAINT fk_cant_material_material FOREIGN KEY (tipo, nombre) REFERENCES material(tipo, nombre)

);
