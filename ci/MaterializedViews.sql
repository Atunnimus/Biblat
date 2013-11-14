CREATE TABLE matviews
(
  mv_name name NOT NULL,
  v_name name NOT NULL,
  last_refresh timestamp with time zone,
  CONSTRAINT matviews_pkey PRIMARY KEY (mv_name )
);

CREATE OR REPLACE FUNCTION create_matview(NAME, NAME)
 RETURNS VOID
 SECURITY DEFINER
 LANGUAGE plpgsql AS '
 DECLARE
     matview ALIAS FOR $1;
     view_name ALIAS FOR $2;
     entry matviews%ROWTYPE;
 BEGIN
     SELECT * INTO entry FROM matviews WHERE mv_name = matview;
 
     IF FOUND THEN
         RAISE EXCEPTION ''Materialized view ''''%'''' already exists.'',
           matview;
     END IF;
 
     EXECUTE ''REVOKE ALL ON '' || view_name || '' FROM PUBLIC''; 
 
     EXECUTE ''GRANT SELECT ON '' || view_name || '' TO PUBLIC'';
 
     EXECUTE ''CREATE TABLE '' || matview || '' AS SELECT * FROM '' || view_name;
 
     EXECUTE ''REVOKE ALL ON '' || matview || '' FROM PUBLIC'';
 
     EXECUTE ''GRANT SELECT ON '' || matview || '' TO PUBLIC'';
 
     INSERT INTO matviews (mv_name, v_name, last_refresh)
       VALUES (matview, view_name, CURRENT_TIMESTAMP); 
     
     RETURN;
 END';

 CREATE OR REPLACE FUNCTION drop_matview(NAME) RETURNS VOID
 SECURITY DEFINER
 LANGUAGE plpgsql AS '
 DECLARE
     matview ALIAS FOR $1;
     entry matviews%ROWTYPE;
 BEGIN
 
     SELECT * INTO entry FROM matviews WHERE mv_name = matview;
 
     IF NOT FOUND THEN
         RAISE EXCEPTION ''Materialized view % does not exist.'', matview;
     END IF;
 
     EXECUTE ''DROP TABLE '' || matview;
     DELETE FROM matviews WHERE mv_name=matview;
 
     RETURN;
 END';

 CREATE OR REPLACE FUNCTION refresh_matview(name) RETURNS VOID
 SECURITY DEFINER
 LANGUAGE plpgsql AS '
 DECLARE 
     matview ALIAS FOR $1;
     entry matviews%ROWTYPE;
 BEGIN
 
     SELECT * INTO entry FROM matviews WHERE mv_name = matview;
 
    IF NOT FOUND THEN
         RAISE EXCEPTION ''Materialized view % does not exist.'', matview;
    END IF;

    EXECUTE ''DELETE FROM '' || matview;
    EXECUTE ''INSERT INTO '' || matview
        || '' SELECT * FROM '' || entry.v_name;

    UPDATE matviews
        SET last_refresh=CURRENT_TIMESTAMP
        WHERE mv_name=matview;

    RETURN;
END';

--Vista para busquedas
CREATE OR REPLACE VIEW "vSearch" AS SELECT 
    t.sistema, 
    t.iddatabase, 
    t.e_245 AS articulo,
    slug(t.e_245) AS "articuloSlug",
    t.e_222 AS revista, 
    slug(t.e_222) AS "revistaSlug", 
    t.e_008 AS pais, 
    slug(t.e_008) AS "paisSlug", 
    t.e_022 AS issn, 
    t.e_041 AS idioma, 
    t.e_260b AS anio, 
    t.e_300a AS volumen, 
    t.e_300b AS numero, 
    t.e_300c AS periodo, 
    t.e_300e AS paginacion, 
    t.e_856u AS url, 
    t.e_590a AS "tipoDocumento",
    t.e_590b AS "enfoqueDocumento",
    t.id_disciplina,
    array_to_json(a."autoresSecArray")::text AS "autoresSecJSON",
    array_to_json(a."autoresSecInstitucionArray")::text AS "autoresSecInstitucionJSON",
    array_to_json(a."autoresArray")::text AS "autoresJSON",
    a."autoresSlug",
    array_to_json(i."institucionesSecArray")::text AS "institucionesSecJSON",
    array_to_json(i."institucionesArray")::text AS "institucionesJSON",
    i."institucionesSlug",
    array_to_json(d."idDisciplinasArray")::text AS "idDisciplinasJSON",
    array_to_json(d."disciplinasArray")::text AS "disciplinasJSON",
    array_to_json(p."palabrasClaveArray")::text AS "palabrasClaveJSON",
    p."palabrasClaveSlug",
    (COALESCE(p."palabrasClaveSlug", '') || 
        COALESCE(slug_space(t.e_245) || ' | ', '') || 
        COALESCE(slug_space(t.e_222) || ' | ', '') || 
        COALESCE(slug_space(t.e_008) || ' | ', '') || 
        COALESCE(i."institucionesSlug", '') || 
        COALESCE(a."autoresSlug", ''))  AS "generalSlug"
FROM articulo t
    LEFT JOIN (SELECT 
            at.iddatabase, 
            at.sistema, 
            array_agg(at.sec_institucion ORDER BY at.sec_autor) AS "autoresSecInstitucionArray",
            array_agg(at.sec_autor ORDER BY at.sec_autor) AS "autoresSecArray",
            array_agg(at.e_100a ORDER BY at.sec_autor) AS "autoresArray",
            string_agg(slug_space(at.e_100a), ' | ' ORDER BY at.sec_autor) || ' | ' AS "autoresSlug"
        FROM autor at
        GROUP BY at.iddatabase, at.sistema) a 
    ON (t.iddatabase=a.iddatabase AND t.sistema=a.sistema) 
    LEFT JOIN (SELECT 
            it.iddatabase, 
            it.sistema, 
            array_agg(it.sec_institucion ORDER BY it.sec_institucion) AS "institucionesSecArray",
            array_agg(it.e_100u ORDER BY it.sec_institucion) AS "institucionesArray",
            string_agg(slug_space(it.e_100u), ' | ' ORDER BY it.sec_institucion) || ' | ' AS "institucionesSlug"
        FROM institucion it
        GROUP BY it.iddatabase, it.sistema) i 
    ON (t.iddatabase=i.iddatabase AND t.sistema=i.sistema)
    LEFT JOIN (SELECT 
            dt.iddatabase, 
            dt.sistema,
            array_agg(dt.iddisciplina ORDER BY dt.iddisciplina) AS "idDisciplinasArray",
            array_agg(dt.disciplina ORDER BY dt.iddisciplina) AS "disciplinasArray"
        FROM artidisciplina dt
        GROUP BY dt.iddatabase, dt.sistema) d 
    ON (t.iddatabase=d.iddatabase AND t.sistema=d.sistema) 
    LEFT JOIN (SELECT 
        pt.iddatabase, 
        pt.sistema, 
        array_agg(pt.descpalabraclave ORDER BY pt.descpalabraclave) AS "palabrasClaveArray", 
        string_agg(slug_space(pt.descpalabraclave), ' | ' ORDER BY pt.descpalabraclave) || ' | ' AS "palabrasClaveSlug"
        FROM palabraclave pt
        GROUP BY pt.iddatabase, pt.sistema) p 
    ON (t.iddatabase=p.iddatabase AND t.sistema=p.sistema);


SELECT create_matview('"mvSearch"', '"vSearch"');

CREATE INDEX "searchSistema_idx" ON "mvSearch"(sistema);
CREATE INDEX "searchIdDatabase_idx" ON "mvSearch"(iddatabase);
CREATE INDEX "searchSistemaIdDatabase_idx" ON "mvSearch"(sistema, iddatabase);
CREATE INDEX "searchIdDisciplina_idx" ON "mvSearch"(id_disciplina);
CREATE INDEX "searchTextoCompleto_idx" ON "mvSearch"(url);
CREATE INDEX "searchArticuloSlug_idx" ON "mvSearch"("articuloSlug");
CREATE INDEX "searchRevistaSlug_idx" ON "mvSearch"("revistaSlug");
CREATE INDEX "searchAlfabetico_idx" ON "mvSearch"(substring(LOWER(revista), 1, 1));
CREATE INDEX "searchHevila_idx" ON "mvSearch" USING gin(url gin_trgm_ops);
#CREATE INDEX "searchGeneralSlug_idx" ON "mvSearch" USING gin(("generalSlug"::tsvector));
CREATE INDEX "searchGeneralSlug_idx" ON "mvSearch" USING gin("generalSlug" gin_trgm_ops);


CREATE OR REPLACE VIEW "vSearchFields" AS SELECT 
    t.sistema, 
    t.iddatabase, 
    unnest(
        array_cat(    
          array_cat(
              array_append(
                array_append(
                    array_append(p."palabrasClaveArray", slug_space(t.e_245)),
                    slug_space(t.e_222)
                ),
                slug_space(t.e_008)
              ),
              i."institucionesArray"
          ),
          a."autoresArray"
        )
    ) AS "singleFields"
FROM articulo t
    LEFT JOIN (SELECT 
            at.iddatabase, 
            at.sistema, 
            array_agg(slug_space(at.e_100a) ORDER BY at.sec_autor) AS "autoresArray"
        FROM autor at
        GROUP BY at.iddatabase, at.sistema) a 
    ON (t.iddatabase=a.iddatabase AND t.sistema=a.sistema) 
    LEFT JOIN (SELECT 
            it.iddatabase, 
            it.sistema, 
            array_agg(slug_space(it.e_100u) ORDER BY it.sec_institucion) AS "institucionesArray"
        FROM institucion it
        GROUP BY it.iddatabase, it.sistema) i 
    ON (t.iddatabase=i.iddatabase AND t.sistema=i.sistema)
    LEFT JOIN (SELECT 
        pt.iddatabase, 
        pt.sistema, 
        array_agg(slug_space(pt.descpalabraclave) ORDER BY pt.descpalabraclave) AS "palabrasClaveArray"
        FROM palabraclave pt
        GROUP BY pt.iddatabase, pt.sistema) p 
    ON (t.iddatabase=p.iddatabase AND t.sistema=p.sistema);

SELECT create_matview('"mvSearchFields"', '"vSearchFields"');

CREATE INDEX "searchFieldSistema_idx" ON "mvSearchFields"(sistema);
CREATE INDEX "searchFieldIdDatabase_idx" ON "mvSearchFields"(iddatabase);
CREATE INDEX "searchFieldSistemaIdDatabase_idx" ON "mvSearchFields"(sistema, iddatabase);
#CREATE INDEX "searchSingleFields_idx" ON "mvSearchFields" USING gin(("singleFields"::tsvector));
CREATE INDEX "searchSingleFields_idx" ON "mvSearchFields" USING gin("singleFields" gin_trgm_ops);

--Vista para lista de paises
CREATE OR REPLACE VIEW "vPais" AS 
SELECT
  "paisSlug",
  pais,
  count(*) as total
  FROM  "vSearch"
  GROUP BY "paisSlug", pais
  ORDER BY "paisSlug";

SELECT create_matview('"mvPais"', '"vPais"');

--Vista para disciplinas
CREATE OR REPLACE VIEW "vDisciplina" AS
SELECT DISTINCT 
  a.id_disciplina, 
  d.disciplina, 
  d.slug, 
  count(*) as total
FROM articulo a INNER JOIN disciplinas d ON a.id_disciplina=d.id_disciplina
GROUP BY a.id_disciplina, d.disciplina, d.slug 
ORDER BY d.disciplina;

SELECT create_matview('"mvDisciplina"', '"vDisciplina"');

--Vista para las revistas por disciplina
CREATE OR REPLACE VIEW "vDisciplinaRevistas" AS
SELECT 
  e_222 as revista, 
  id_disciplina, 
  count(*) as documentos 
FROM articulo 
GROUP BY id_disciplina, e_222 
ORDER BY id_disciplina;

SELECT create_matview('"mvDisciplinaRevistas"', '"vDisciplinaRevistas"');


--Vista para mostrar solo los documentos que sean artículos y mostrando el año en una cadena de 4 digitos
CREATE OR REPLACE VIEW "vArticulos" AS
SELECT 
  iddatabase,
  sistema,
  id_disciplina,
  e_222 AS revista,
  slug(e_222) AS "revistaSlug",
  e_300a,
  e_300b,
  substr(e_260b, 1, 4) AS anio
FROM articulo WHERE e_590a ~~ 'Artículo%' AND substr(e_260b, 1, 4) ~ '[0-9]{4}';

--Autores por documento y pais de aficialción
CREATE OR REPLACE VIEW "vAutoresDocumentoPais" AS
SELECT dp.iddatabase,
       dp.sistema,
       dp.e_100x,
       sum(ad.autores) AS autores
FROM
  (SELECT a.iddatabase,
          a.sistema,
          e_100x
   FROM autor a
   INNER JOIN institucion i ON a.iddatabase=i.iddatabase
   AND a.sistema=i.sistema
   AND a.sec_autor=i.sec_autor
   AND a.sec_institucion=i.sec_institucion
   WHERE e_100x IS NOT NULL
   GROUP BY a.iddatabase, a.sistema, e_100x) AS dp --dp => documento y pais de afiliacion
INNER JOIN
  (SELECT a.iddatabase,
          a.sistema,
          count(*) AS autores
   FROM autor a
   LEFT JOIN institucion i ON a.iddatabase=i.iddatabase
   AND a.sistema=i.sistema
   AND a.sec_autor=i.sec_autor
   AND a.sec_institucion=i.sec_institucion
   GROUP BY a.iddatabase, a.sistema) AS ad -- ad => autores por documento
ON dp.iddatabase=ad.iddatabase
AND dp.sistema=ad.sistema
GROUP BY dp.iddatabase, dp.sistema, dp.e_100x;

  --Autores por documento
CREATE OR REPLACE VIEW "vAutoresDocumento" AS
SELECT * FROM
(SELECT a.iddatabase,
       a.sistema,
       count(*) AS autores,
       max(i.e_100x) AS e_100x
FROM autor a
LEFT JOIN institucion i ON a.iddatabase=i.iddatabase
AND a.sistema=i.sistema
AND a.sec_autor=i.sec_autor
AND a.sec_institucion=i.sec_institucion
GROUP BY a.iddatabase, a.sistema) AS ad WHERE ad.e_100x IS NOT NULL;

--Indice de coautoria por revista
CREATE OR REPLACE VIEW "vIndiceCoautoriaPriceRevista" AS
SELECT ar.e_222 AS revista,
       slug(ar.e_222) AS "revistaSlug",
       substr(ar.e_260b, 1, 4) AS anio,
       count(*) AS documentos,
       sum(au.autores) AS autores,
       sum(au.autores) / count(*) AS coautoria,
       sqrt(sum(au.autores)) AS price
FROM "vAutoresDocumento" au
INNER JOIN articulo ar ON au.iddatabase=ar.iddatabase
AND au.sistema=ar.sistema
WHERE ar.e_590a ~~ 'Artículo%'
  AND substr(ar.e_260b, 1, 4) ~ '[0-9]{4}'
GROUP BY revista, anio
ORDER BY revista, anio;

SELECT create_matview('"mvIndiceCoautoriaPriceRevista"', '"vIndiceCoautoriaPriceRevista"');
CREATE INDEX "indiceCoautoriaPriceRevista_resvistaSlug" ON "mvIndiceCoautoriaPriceRevista"("revistaSlug");
CREATE INDEX "indiceCoautoriaPriceRevista_anio" ON "mvIndiceCoautoriaPriceRevista"(anio);

--Indice de coautoria por país
CREATE OR REPLACE VIEW "vIndiceCoautoriaPricePais" AS
SELECT 
  au.e_100x AS "paisAutor", 
  slug(au.e_100x) AS "paisAutorSlug", 
  id_disciplina, 
  substr(ar.e_260b, 1, 4) AS anio, 
  count(*) as documentos, 
  sum(au.autores) as autores, 
  sum(au.autores) / count(*) AS coautoria,
  sqrt(sum(au.autores)) AS price
FROM "vAutoresDocumentoPais" au
INNER JOIN  articulo ar 
ON au.iddatabase=ar.iddatabase AND au.sistema=ar.sistema
WHERE ar.e_590a ~~ 'Artículo%' AND substr(ar.e_260b, 1, 4) ~ '[0-9]{4}'
GROUP BY "paisAutor", id_disciplina, anio
ORDER BY "paisAutor", id_disciplina, anio;

SELECT create_matview('"mvIndiceCoautoriaPricePais"', '"vIndiceCoautoriaPricePais"');
CREATE INDEX "indiceCoautoriaPricePais_paisAutorSlug" ON "mvIndiceCoautoriaPricePais"("paisAutorSlug");
CREATE INDEX "indiceCoautoriaPricePais_anio" ON "mvIndiceCoautoriaPricePais"(anio);
CREATE INDEX "indiceCoautoriaPricePais_idDisciplina" ON "mvIndiceCoautoriaPricePais"(id_disciplina);

--Vista para revistans con años continuos mayores a 4
CREATE OR REPLACE VIEW "vDisciplinaRevistasContinuos" AS
SELECT dr.revista,
       dr."revistaSlug",
       dr.id_disciplina,
       dr.documentos,
       ac.anios_continuos
FROM
  (SELECT "revistaSlug",
          anios_continuos(array_agg(anio))
   FROM "vIndiceCoautoriaPriceRevista"
   GROUP BY "revistaSlug") AS ac --Años continuos por revista
INNER JOIN "vDisciplinaRevistas" dr ON ac."revistaSlug"=dr."revistaSlug"
WHERE anios_continuos > 4;


SELECT create_matview('"mvDisciplinaRevistasContinuos"', '"vDisciplinaRevistasContinuos"');

--Vista para paises con años continuos mayores a 4
CREATE OR REPLACE VIEW "vDisciplinaPaisesContinuos" AS
SELECT *
FROM
  (SELECT "paisAutor",
          "paisAutorSlug",
          id_disciplina,
          anios_continuos(array_agg(anio))
   FROM "vIndiceCoautoriaPricePais"
   GROUP BY "paisAutorSlug",
            "paisAutor",
            id_disciplina
   ORDER BY "paisAutorSlug",
            id_disciplina) AS ac --Años continuos por revista
WHERE anios_continuos > 4;


SELECT create_matview('"mvDisciplinaPaisesContinuos"', '"vDisciplinaPaisesContinuos"');

--Vista para tasa de coutoria por revista
CREATE OR REPLACE VIEW "vTasaCoautoriaRevista" AS
SELECT td.revista,
       td."revistaSlug",
       td.anio,
       td.documentos AS "totalDocumentos",
       tda.documentos AS "documentosMultiple",
       (tda.documentos::numeric/td.documentos::numeric) AS "tasaCoautoria"
FROM "vIndiceCoautoriaPriceRevista" td --Total de documentos
INNER JOIN
  (SELECT ar."revistaSlug",
          ar.anio,
          count(*) AS documentos
   FROM "vArticulos" ar
   INNER JOIN "vAutoresDocumento" au ON ar.iddatabase=au.iddatabase AND ar.sistema=au.sistema AND au.autores>1
   GROUP BY "revistaSlug", anio) AS tda --Total de documentos con mas de un autor
ON td."revistaSlug"=tda."revistaSlug" AND td.anio=tda.anio;


SELECT create_matview('"mvTasaCoautoriaRevista"', '"vTasaCoautoriaRevista"');
CREATE INDEX "tasaCoautoriaRevista_resvistaSlug" ON "mvTasaCoautoriaRevista"("revistaSlug");
CREATE INDEX "tasaCoautoriaRevista_anio" ON "mvTasaCoautoriaRevista"(anio);

--Vista para tasa de coutoria por pais
CREATE OR REPLACE VIEW "vTasaCoautoriaPais" AS
SELECT 
  td."paisAutor",
  td."paisAutorSlug",
  td.id_disciplina,
  td.anio,
  td.documentos AS "totalDocumentos",
  tda.documentos AS "documentosMultiple",
  (tda.documentos::numeric/td.documentos::numeric) AS "tasaCoautoria"
FROM "vIndiceCoautoriaPricePais" td --Total de documentos
INNER JOIN
(SELECT 
  slug(au.e_100x) AS "paisAutorSlug", 
  id_disciplina, 
  anio, 
  count(*) as documentos
FROM "vArticulos" ar
INNER JOIN "vAutoresDocumentoPais" au 
ON ar.iddatabase=au.iddatabase AND ar.sistema=au.sistema AND au.autores>1
GROUP BY "paisAutorSlug", id_disciplina, anio) AS tda --Documentos con más de un autor
ON td."paisAutorSlug"=tda."paisAutorSlug" AND td.id_disciplina=tda.id_disciplina AND td.anio=tda.anio;

SELECT create_matview('"mvTasaCoautoriaPais"', '"vTasaCoautoriaPais"');
CREATE INDEX "tasaCoautoriaPais_resvistaSlug" ON "mvTasaCoautoriaPais"("paisAutorSlug");
CREATE INDEX "tasaCoautoriaPais_anio" ON "mvTasaCoautoriaPais"(anio);
CREATE INDEX "tasaCoautoriaPais_idDisciplina" ON "mvTasaCoautoriaPais"(id_disciplina);

--Vista lawani por revista
CREATE OR REPLACE VIEW "vLawaniRevista" AS
SELECT td.revista,
       td."revistaSlug",
       td.anio,
       td.documentos AS "totalDocumentos",
       sad."autoresXdocumentos",
       sad."autoresXdocumentos"::numeric/td.documentos::numeric AS lawani
FROM "vIndiceCoautoriaPriceRevista" td --Total de documentos
INNER JOIN
  (SELECT "revistaSlug",
          anio,
          sum("autoresXdocumentos") AS "autoresXdocumentos"
   FROM
     (SELECT a."revistaSlug",
             a.anio,
             ad.autores * count(*) AS "autoresXdocumentos"
      FROM "vAutoresDocumento" ad --autores por documento
INNER JOIN "vArticulos" a ON ad.iddatabase=a.iddatabase
      AND ad.sistema=a.sistema
      AND ad.autores>1
      GROUP BY a."revistaSlug",
               a.anio,
               ad.autores) adr -- autoresXdocumento por revista al año
GROUP BY "revistaSlug",
         anio) sad -- suma de autoresXdocumento por revista al año
ON td."revistaSlug"=sad."revistaSlug"
AND td.anio=sad.anio;

SELECT create_matview('"mvLawaniRevista"', '"vLawaniRevista"');

--Vista lawani por país
CREATE OR REPLACE VIEW "vLawaniPais" AS
SELECT
  td."paisAutor", 
  td."paisAutorSlug",
  td.id_disciplina,
  td.anio,
  sadp."autoresXdocumentos"::numeric/td.documentos::numeric AS lawani 
FROM "vIndiceCoautoriaPricePais" td --Total de documentos
INNER JOIN
(SELECT 

  "paisAutorSlug",
  id_disciplina,
  anio,
  sum("autoresXdocumentos") AS "autoresXdocumentos"
FROM
  (SELECT 
    slug(adp.e_100x) AS "paisAutorSlug",
    id_disciplina,
    anio,
    --autores, count(*) AS documentos,
    autores * count(*) AS "autoresXdocumentos"
  FROM "vAutoresDocumentoPais" adp
  INNER JOIN "vArticulos" a
  ON adp.iddatabase=a.iddatabase AND adp.sistema=a.sistema AND adp.autores>1
  GROUP BY "paisAutorSlug", a.id_disciplina, a.anio, adp.autores) AS adp --Autores por documentos en pais, disciplina y año
GROUP BY "paisAutorSlug", id_disciplina, anio) AS sadp --Suma de autores por documentos en pais, disciplina y año
ON td."paisAutorSlug"=sadp."paisAutorSlug" AND td.id_disciplina=sadp.id_disciplina AND td.anio=sadp.anio;

SELECT create_matview('"mvLawaniPais"', '"vLawaniPais"');

-- Vista para inide subramayan por revista
CREATE OR REPLACE VIEW "vSubramayanRevista" AS
SELECT
  am.revista,
  am."revistaSlug",
  am.anio,
  am.documentos AS "documentosMultiple",
  au.documentos AS "documentosUnAutor",
  am.documentos::numeric/(au.documentos+am.documentos)::numeric AS subramayan
FROM
(SELECT max(ar.revista) as revista,
    ar."revistaSlug",
          ar.anio,
          count(*) AS documentos
   FROM "vArticulos" ar
   INNER JOIN "vAutoresDocumento" au ON ar.iddatabase=au.iddatabase AND ar.sistema=au.sistema AND au.autores>1
   GROUP BY "revistaSlug", anio) am -- Autores multiples
INNER JOIN
(SELECT   ar."revistaSlug",
          ar.anio,
          count(*) AS documentos
   FROM "vArticulos" ar
   INNER JOIN "vAutoresDocumento" au ON ar.iddatabase=au.iddatabase AND ar.sistema=au.sistema AND au.autores=1
   GROUP BY "revistaSlug", anio) au --Autores unicos
ON am."revistaSlug"=au."revistaSlug" AND am.anio=au.anio;

SELECT create_matview('"mvSubramayanRevista"', '"vSubramayanRevista"');

--Vista para indice subramayan por país
CREATE OR REPLACE VIEW "vSubramayanPais" AS
SELECT
  am."paisAutor",
  am."paisAutorSlug",
  am.id_disciplina,
  am.documentos AS "documentosMultiple",
  au.documentos AS "documentosUnAutor",
  am.documentos::numeric/(au.documentos+am.documentos)::numeric AS subramayan
FROM 
(SELECT 
  max(au.e_100x) as "paisAutor",
  slug(au.e_100x) AS "paisAutorSlug", 
  id_disciplina, 
  anio, 
  count(*) as documentos
FROM "vArticulos" ar
INNER JOIN "vAutoresDocumentoPais" au 
ON ar.iddatabase=au.iddatabase AND ar.sistema=au.sistema AND au.autores>1
GROUP BY "paisAutorSlug", id_disciplina, anio) am -- Autores multiples
INNER JOIN 
(SELECT 
  slug(au.e_100x) AS "paisAutorSlug", 
  id_disciplina, 
  anio, 
  count(*) as documentos
FROM "vArticulos" ar
INNER JOIN "vAutoresDocumentoPais" au 
ON ar.iddatabase=au.iddatabase AND ar.sistema=au.sistema AND au.autores=1
GROUP BY "paisAutorSlug", id_disciplina, anio) au --Autores unicos
ON am."paisAutorSlug"=au."paisAutorSlug" AND am.id_disciplina=au.id_disciplina AND am.anio=au.anio;

SELECT create_matview('"mvSubramayanPais"', '"vSubramayanPais"');

-- Vista para inidice zakutina por revista

CREATE OR REPLACE VIEW "vZakutinaRevista" AS
SELECT td.revista,
       td."revistaSlug",
       td.anio,
       td.documentos AS "totalDocumentos",
       t.titulos,
       (td.documentos::numeric/t.titulos::numeric) AS zakutina
FROM "vIndiceCoautoriaPriceRevista" td --Total de documentos
INNER JOIN
(SELECT "revistaSlug", anio, count(*) AS titulos FROM (SELECT "revistaSlug", anio, e_300a, e_300b FROM 
"vAutoresDocumento" ad 
INNER JOIN
"vArticulos" a
ON ad.iddatabase=a.iddatabase AND ad.sistema=a.sistema
GROUP BY "revistaSlug", anio, e_300a, e_300b) ravn -- Revista, año, volumen, numero

GROUP BY "revistaSlug", anio) t --Titulos por revista al año
ON td."revistaSlug"=t."revistaSlug" AND td.anio=t.anio;

SELECT create_matview('"mvZakutinaRevista"', '"vZakutinaRevista"');

--Vista para indice zakutina por país
CREATE OR REPLACE VIEW "vZakutinaPais" AS
SELECT 
  td."paisAutor",
  td."paisAutorSlug",
  td.id_disciplina,
  td.anio,
  td.documentos AS "totalDocumentos",
  t.titulos,
  (td.documentos::numeric/t.titulos::numeric) AS zakutina
FROM "vIndiceCoautoriaPricePais" td --Total de documentos
INNER JOIN
(SELECT "paisAutorSlug", id_disciplina, anio, count(*) AS titulos FROM (SELECT slug(e_100x) AS "paisAutorSlug", id_disciplina, anio, e_300a, e_300b FROM 
"vAutoresDocumentoPais" ad 
INNER JOIN
"vArticulos" a
ON ad.iddatabase=a.iddatabase AND ad.sistema=a.sistema
GROUP BY "paisAutorSlug", id_disciplina, anio, e_300a, e_300b) ravn -- pais, disciplina, año, volumen, numero
GROUP BY "paisAutorSlug", id_disciplina, anio) t --Titulos por pais al año
ON td."paisAutorSlug"=t."paisAutorSlug" AND td.id_disciplina=t.id_disciplina AND td.anio=t.anio;

SELECT create_matview('"mvZakutinaPais"', '"vZakutinaPais"');