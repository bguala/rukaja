CREATE TABLE  unidad_academica (

sigla character varying (6) NOT NULL,
descripcion character varying (50),
CONSTRAINT pk_unidad_academica PRIMARY KEY (sigla)

);

INSERT INTO unidad_academica (sigla,descripcion) VALUES
('FAIN','Facultad de Ingenier�a'),
('FAI', 'Facultad de Inform�tica'),
('FAEA','Facultad de Econom�a y Administraci�n'),
('FATU','Facultad de Turismo'),
('FAHU','Facultad de Humanidades'),
('RECT','Administraci�n Central'),
('BIBLIO','Biblioteca'),
('FACIAS','Facultad de Ciencias del Ambiente y la Salud');

CREATE TABLE  sede (

id_sede serial NOT NULL,
descripcion character varying (20),
telefono character varying (20),
direccion character varying (35),
sigla character varying (6), 

CONSTRAINT pk_sede PRIMARY KEY (id_sede),
CONSTRAINT fk_unidad FOREIGN KEY (sigla) REFERENCES unidad_academica(sigla)

);

INSERT INTO sede (id_sede,descripcion,sigla,telefono,direccion) VALUES
(1,'Neuqu�n Capital', 'RECT', '+54 0299 4490363', 'Buenos Aires 1400'),
(2,'Neuqu�n Capital', 'FACIAS', '54 (0299) 4490357', 'Buenos Aires 1400'),
(3,'Neuqu�n Capital', 'FAEA', '54 (0299) 4490312', 'Buenos Aires 1400'),
(4,'Neuqu�n Capital', 'FATU', '54 0299 4490378', 'Buenos Aires 1400'),
(5,'Neuqu�n Capital', 'FAI', '+54 0299 4490300', 'Buenos Aires 1400'),
(6,'Neuqu�n Capital', 'FAHU', '54 0299 4490388', 'Buenos Aires 1400'),
(7,'Neuqu�n Capital', 'FAIN', '+54 0299 4490368', 'Buenos Aires 1400'),
(8,'Neuqu�n Capital', 'BIBLIO', '+54 0299 4490398', 'Buenos Aires 1400');

CREATE TABLE  tipo (

id_tipo character varying (2) NOT NULL,
descripcion character varying,

CONSTRAINT pk_tipo PRIMARY KEY (id_tipo)

);

--A=Aula comun identifica aulas con pizarra y pupitre para dar clases.
--B=Eventos identifica a salones sin pupitres como el sal�n azul.
--C=Laboratorios identifica salas de m�quinas(con computadoras).

INSERT INTO tipo (id_tipo,descripcion) VALUES
('A','Aula de uso com�n'),
('B','Espacio para eventos'),
('C','Laboratorio'),
('D','Aula Propia de Unidad Acad�mica');

CREATE TABLE  aula (

id_aula serial NOT NULL,
capacidad integer,
nombre character varying,
ubicacion character varying, -- Para indicar la ubicacion del aula.
id_tipo character varying (2),
id_sede serial,
eliminada boolean,
imagen bytea,

CONSTRAINT pk_aula PRIMARY KEY (id_aula),
CONSTRAINT fk_aula FOREIGN KEY (id_tipo) REFERENCES tipo(id_tipo) ,
CONSTRAINT fk_aula_sede FOREIGN KEY (id_sede) REFERENCES sede(id_sede) 

);

INSERT INTO aula (id_aula,capacidad,nombre,ubicacion,id_tipo,id_sede) VALUES
(1, 80, '101', 'Se encuentra ubicada en el complejo de Aulas Comunes del Rectorado Ar�stides Romero', 'A', 1),
(2, 80, '102', 'Se encuentra ubicada en el complejo de Aulas Comunes del Rectorado Ar�stides Romero', 'A', 1),
(3, 150, '105', 'Se encuentra ubicada en el complejo de Aulas Comunes del Rectorado Ar�stides Romero en planta alta', 'A', 1),
(4, 300, '106', 'Se encuentra ubicada en el complejo de Aulas Comunes del Rectorado Ar�stides Romero. A la izquierda del Departamento de Alumnos de FAI', 'A', 1),
(5, 300, '107', 'Se encuentra ubicada en el complejo de Aulas Comunes del Rectorado Ar�stides Romero. A la derecha del Departamento de Alumnos de FAI', 'A', 1),
(6, 230, '13', 'Se encuentra ubicada en el edificio del Rectorado o Administraci�n Central', 'A', 1),
(7, 50, '13,1', 'Se encuentra ubicada en el edificio del Rectorado o Administraci�n Central', 'A', 1),
(8, 140, '13,2', 'Se encuentra ubicada en el edificio del Rectorado o Administraci�n Central', 'A', 1),
(9, 230, '15', 'Se encuentra ubicada en el edificio del Rectorado o Administraci�n Central','A', 1),
(10, 230, '16', 'Se encuentra ubicada en el edificio del Rectorado o Administraci�n Central', 'A', 1),
(11, 80, '17', 'Se encuentra ubicada en el edificio del Rectorado o Administraci�n Central', 'A', 1),
(12, 60, '19', 'Se encuentra ubicada en el edificio del Rectorado o Administraci�n Central', 'A', 1),
(13, 60, '21', 'Se encuentra ubicada en el edificio del Rectorado o Administraci�n Central', 'A', 1),
(14, 80, '24', 'Se encuentra ubicada en el edificio del Rectorado o Administraci�n Central', 'A', 1),
(15, 200, '25', 'Se encuentra ubicada en el edificio del Rectorado o Administraci�n Central', 'A', 1),
(16, 60, '26', 'Se encuentra ubicada en el edificio del Rectorado o Administraci�n Central', 'A', 1);

--Reseteamos la secuencia para evitar problemas de PK duplicadas.
SELECT setval('aula_id_aula_seq', 16, 't');

CREATE TABLE  dia (

nombre character varying(10) NOT NULL,
orden integer,

CONSTRAINT pk_dia PRIMARY KEY (nombre)

);

INSERT INTO dia (nombre,orden) VALUES
('Lunes', 1),
('Martes', 2),
('Mi�rcoles', 3),
('Jueves', 4),
('Viernes', 5),
('S�bado', 6),
('Domingo', 7);

CREATE TABLE  persona (

nro_doc character varying (20) NOT NULL,
tipo_doc character varying (12) NOT NULL,
nombre character varying (35),
apellido character varying (35),
telefono character varying (20),
correo_electronico character varying (50),
domicilio character varying (35),
ciudad character varying (30),

CONSTRAINT pk_persona PRIMARY KEY (tipo_doc,nro_doc)

);

CREATE TABLE  administrador (

id_administrador serial NOT NULL,
nombre_usuario character varying (35),
id_sede serial,
nombre character varying(35),
apellido character varying(35),
correo_electronico character varying(50),
CONSTRAINT pk_admin PRIMARY KEY (id_administrador),
CONSTRAINT fk_sede FOREIGN KEY (id_sede) REFERENCES sede(id_sede) 

);

CREATE TABLE  docente (

nro_doc character varying (20) NOT NULL,
tipo_doc character varying (12) NOT NULL,
legajo integer,
titulo character varying (70),
CONSTRAINT pk_docente PRIMARY KEY (tipo_doc,nro_doc),
CONSTRAINT fk_docente FOREIGN KEY (tipo_doc,nro_doc) REFERENCES persona(tipo_doc,nro_doc)  

);

CREATE TABLE  tipo_asignacion (

tipo character varying NOT NULL,

CONSTRAINT pk_tipo_asignacion PRIMARY KEY (tipo)

);

INSERT INTO tipo_asignacion (tipo) VALUES 
('EXAMEN PARCIAL'),
('CURSADA'),
('EXAMEN FINAL'),
('EVENTO'),
('CONSULTA');

CREATE TABLE  periodo (

id_periodo serial NOT NULL,
fecha_inicio date,
fecha_fin date,
anio_lectivo integer,
id_sede integer,

CONSTRAINT pk_perido PRIMARY KEY (id_periodo)

);

CREATE TABLE  cuatrimestre (

id_periodo serial NOT NULL,
numero integer,

CONSTRAINT pk_cuatrimestre PRIMARY KEY (id_periodo),
CONSTRAINT fk_periodo_cuatrimestre FOREIGN KEY (id_periodo) REFERENCES periodo(id_periodo) 
ON DELETE CASCADE ON UPDATE CASCADE 

);

CREATE TABLE  tipo_examen (

turno character varying (15) NOT NULL,
tipo character varying (15), --puede ser ordinario o extraordinario

CONSTRAINT pk_tipo_examen PRIMARY KEY (turno)

);

INSERT INTO tipo_examen (turno, tipo) VALUES 

('FEBRERO-MARZO', 'ORDINARIO'),
('JULIO-AGOSTO', 'ORDINARIO'),
('DICIEMBRE', 'ORDINARIO'),
('ABRIL', 'EXTRAORDINARIO'),
('MAYO', 'EXTRAORDINARIO'),
('SEPTIEMBRE', 'EXTRAORDINARIO'),
('OCTUBRE', 'EXTRAORDINARIO');

CREATE TABLE  examen_final (

id_periodo serial NOT NULL,
numero integer,
turno character varying (15),

CONSTRAINT pk_periodo PRIMARY KEY (id_periodo),
CONSTRAINT fk_periodo_examen_final FOREIGN KEY (id_periodo) REFERENCES periodo(id_periodo) 
ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT fk_tipo_examen FOREIGN KEY (turno) REFERENCES tipo_examen(turno) ON DELETE CASCADE
ON UPDATE CASCADE

);

CREATE TABLE  curso_ingreso (

id_periodo serial NOT NULL,
facultad character varying (6),
nombre character varying (50),

CONSTRAINT pk_curso_ingreso PRIMARY KEY (id_periodo),
CONSTRAINT fk_periodo_curso_ingreso FOREIGN KEY (id_periodo) REFERENCES periodo(id_periodo) 
ON DELETE CASCADE ON UPDATE CASCADE

);

CREATE TABLE  asignacion (

  id_asignacion serial NOT NULL,
  finalidad character varying,
  descripcion character varying,
  hora_inicio time without time zone,
  hora_fin time without time zone,
  cantidad_alumnos integer,
  facultad character varying(6),
  nro_doc character varying(20),
  tipo_doc character varying(12),
  id_aula integer,
  modulo integer,
  tipo_asignacion character varying,
  id_periodo integer,
  id_responsable_aula integer,
  nombre character varying,
  
  apellido character varying,

  legajo integer,

  CONSTRAINT pk_asignacion PRIMARY KEY (id_asignacion),
  CONSTRAINT fk_asignacion_aula FOREIGN KEY (id_aula)
      REFERENCES aula (id_aula) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_asignacion_periodo FOREIGN KEY (id_periodo)
      REFERENCES periodo (id_periodo) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_asignacion_tipo_asignacion FOREIGN KEY (tipo_asignacion)
      REFERENCES tipo_asignacion (tipo) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
  
);

CREATE TABLE  asignacion_definitiva
(
  nombre character varying(10),
  id_asignacion integer NOT NULL,
  CONSTRAINT pk_asignacion_definitiva PRIMARY KEY (id_asignacion),
  CONSTRAINT fk_asignacion_definitiva FOREIGN KEY (id_asignacion)
      REFERENCES asignacion (id_asignacion) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_asignacion_definitiva_nombre FOREIGN KEY (nombre)
      REFERENCES dia (nombre) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

CREATE TABLE  asignacion_periodo
(
  id_asignacion serial NOT NULL,
  fecha_inicio date,
  fecha_fin date,

  CONSTRAINT pk_asignacion_periodo PRIMARY KEY (id_asignacion),
  CONSTRAINT fk_asignacion_periodo FOREIGN KEY (id_asignacion)
      REFERENCES asignacion (id_asignacion) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE  esta_formada
(
  nombre character varying(10) NOT NULL,
  id_asignacion serial NOT NULL,
  fecha date NOT NULL,

  CONSTRAINT pk_esta_formada PRIMARY KEY (id_asignacion, fecha),
  CONSTRAINT fk_esta_formada_asignacion FOREIGN KEY (id_asignacion)
      REFERENCES asignacion_periodo (id_asignacion) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_esta_formada_dia FOREIGN KEY (nombre)
      REFERENCES dia (nombre) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

CREATE TABLE  solicitud (

  id_solicitud serial NOT NULL,
  nombre character varying,  --Contiene nombre-apelido del docente o el nombre de una org.
  tipo character varying(5),
  tipo_agente character varying, --Agregamos el tipo de agente para distinguir entre docente u org.
  fecha date,
  capacidad integer,
  finalidad character varying,
  hora_inicio time without time zone,
  hora_fin time without time zone,
  tipo_asignacion character varying,
  id_sede serial NOT NULL,       --Guardamos quien recibe el pedido de aula.
  estado character varying,
  id_responsable integer,
  id_aula integer,
  id_sede_origen integer,         --Guardamos quien hizo el pedido de aula.
  facultad character varying(6),

  CONSTRAINT pk_solicitud PRIMARY KEY (id_solicitud),
  CONSTRAINT fk_solicitud FOREIGN KEY (id_sede)
      REFERENCES sede (id_sede) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

CREATE TABLE  solicitud_multi_evento (

id_solicitud serial NOT NULL,
fecha_fin date,

CONSTRAINT pk_solicitud_multi_evento PRIMARY KEY (id_solicitud),
CONSTRAINT fk_solicitud_multi_evento FOREIGN KEY (id_solicitud)	REFERENCES solicitud(id_solicitud) ON DELETE CASCADE ON UPDATE CASCADE

);

CREATE TABLE  multi_evento (

id_solicitud serial NOT NULL,
fecha date NOT NULL,
nombre character varying (10) NOT NULL,

CONSTRAINT pk_multi_evento PRIMARY KEY (id_solicitud, fecha, nombre),
CONSTRAINT fk_multi_evento FOREIGN KEY (id_solicitud)
      REFERENCES solicitud_multi_evento (id_solicitud) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE

);

CREATE TABLE  catedra (

id_asignacion serial NOT NULL,
nro_doc character varying (20),
tipo_doc character varying (12),
id_docente integer NOT NULL,
nombre character varying,

apellido character varying,
legajo integer,

CONSTRAINT pk_catedra PRIMARY KEY (id_asignacion,id_docente),
CONSTRAINT fk_asignacion_catedra FOREIGN KEY (id_asignacion)
      REFERENCES asignacion (id_asignacion) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
	  
);

CREATE TABLE  organizacion (

id_organizacion serial NOT NULL,
nombre character varying,
telefono character varying,
email character varying,

CONSTRAINT pk_organizacion PRIMARY KEY (id_organizacion)

);

--Instalamos el modulo dblink para relacionar bases de datos a traves de una misma consulta.
--create extension dblink;

--Vista relacionada a la tabla docente de la base de datos mocovi.
--create view docentes as  
--	select * from dblink('host=localhost port=5432 dbname=mocovi user=postgres password=brunoguala', 'select * from docente') as 

--		 (id_docente integer,
--		  legajo integer,  
--		  apellido character varying,
--		  nombre character varying,
--		  tipo_docum character(4),
--		  nro_docum integer) ;

