-- =============================================================================
-- Имя модели: LK
-- Создано: 02-11-2010 17:52:04
-- Версия модели: 1
-- =============================================================================



CREATE TABLE "users" (
	"id" SERIAL NOT NULL,
	"email" varchar(100) NOT NULL,
	"jur" bool NOT NULL DEFAULT False,
	"password" varchar(100),
	"first_name" varchar NOT NULL,
	"midle_name" varchar NOT NULL,
	"last_name" varchar NOT NULL,
	"birthday" int4,
	"sex" bool NOT NULL DEFAULT True,
  PRIMARY KEY("id")
);


CREATE TABLE "user_address" (
	"id" SERIAL NOT NULL,
	"user_id" int4,
	"region_id" int4 NOT NULL DEFAULT 1,
	"city" varchar NOT NULL,
	"metro" varchar NOT NULL,
	"street" varchar NOT NULL,
	"house" varchar NOT NULL,
	"housing" varchar,
	"build" varchar,
	"office" varchar,
	"porch" varchar,
	"floor" varchar,
	"intercom" varchar,
	"apartment" varchar NOT NULL,
	"phone" int8 NOT NULL,
	"mobile" int8 NOT NULL,
	"fax" int8,
	"lift" bool NOT NULL DEFAULT False,
	"maillist" bool NOT NULL DEFAULT False,
	"sort" int4,
	"default" bool,
  PRIMARY KEY("id")
);


CREATE TABLE "user_jur" (
	"company_id" SERIAL NOT NULL,
	"organization" varchar(255) NOT NULL,
	"bank" varchar NOT NULL,
	"jur_addr" varchar NOT NULL,
	"rs" int4 NOT NULL,
	"ks" int4 NOT NULL,
	"bik" int4 NOT NULL,
	"inn" int4 NOT NULL,
	"kpp" int4 NOT NULL,
	"okdp" int4,
	"okpo" int4,
	"okonh" int4,
	"okved" int4,
	"www" varchar,
  PRIMARY KEY("company_id")
);


COMMENT ON COLUMN "user_jur"."organization" IS 'Название организации';

COMMENT ON COLUMN "user_jur"."rs" IS 'Р/с';

COMMENT ON COLUMN "user_jur"."inn" IS 'ИНН';

CREATE TABLE "user_session" (
	"id" SERIAL NOT NULL,
	"session_data_id" int4,
	"name" varchar(50) NOT NULL,
  PRIMARY KEY("id")
);


CREATE TABLE "user_siebel" (
	"id" SERIAL NOT NULL,
	"session_id" int4,
	"siebel_id" int4 NOT NULL,
  PRIMARY KEY("id")
);


CREATE TABLE "session_data" (
	"id" SERIAL NOT NULL,
	"data" text,
  PRIMARY KEY("id")
);


CREATE TABLE "kis_regions" (
	"region_id" SERIAL NOT NULL,
	"region_name" text,
  PRIMARY KEY("region_id")
)
WITHOUT OIDS;


GRANT ALL PRIVILEGES ON TABLE "kis_regions" TO PUBLIC;

CREATE TABLE "users_has_user_jur" (
	"NMID" SERIAL NOT NULL,
	"id" int4 NOT NULL,
	"company_id" int4 NOT NULL,
  PRIMARY KEY("NMID")
);



ALTER TABLE "user_address" ADD CONSTRAINT "Ref_user_adress_to_users" FOREIGN KEY ("user_id")
	REFERENCES "users"("id")
	MATCH SIMPLE
	ON DELETE CASCADE
	ON UPDATE CASCADE
	NOT DEFERRABLE;

ALTER TABLE "user_address" ADD CONSTRAINT "Ref_user_address_to_kis_regions" FOREIGN KEY ("region_id")
	REFERENCES "kis_regions"("region_id")
	MATCH SIMPLE
	ON DELETE SET NULL
	ON UPDATE CASCADE
	NOT DEFERRABLE;

ALTER TABLE "user_session" ADD CONSTRAINT "Ref_user_session_to_users" FOREIGN KEY ("id")
	REFERENCES "users"("id")
	MATCH SIMPLE
	ON DELETE CASCADE
	ON UPDATE CASCADE
	NOT DEFERRABLE;

ALTER TABLE "user_session" ADD CONSTRAINT "Ref_user_session_to_session_data" FOREIGN KEY ("session_data_id")
	REFERENCES "session_data"("id")
	MATCH SIMPLE
	ON DELETE CASCADE
	ON UPDATE CASCADE
	NOT DEFERRABLE;

ALTER TABLE "user_siebel" ADD CONSTRAINT "Ref_user_siebel_to_user_session" FOREIGN KEY ("session_id")
	REFERENCES "user_session"("id")
	MATCH SIMPLE
	ON DELETE SET NULL
	ON UPDATE CASCADE
	NOT DEFERRABLE;

ALTER TABLE "users_has_user_jur" ADD CONSTRAINT "Ref_users_has_user_jur_to_users" FOREIGN KEY ("id")
	REFERENCES "users"("id")
	MATCH SIMPLE
	ON DELETE CASCADE
	ON UPDATE CASCADE
	NOT DEFERRABLE;

ALTER TABLE "users_has_user_jur" ADD CONSTRAINT "Ref_users_has_user_jur_to_user_jur" FOREIGN KEY ("company_id")
	REFERENCES "user_jur"("company_id")
	MATCH SIMPLE
	ON DELETE CASCADE
	ON UPDATE CASCADE
	NOT DEFERRABLE;


CREATE OR REPLACE VIEW "user_info_view" AS
	SELECT
  public.users.id,
  public.users.email,
  public.users.jur,
  public.users.password,
  public.users.first_name,
  public.users.midle_name,
  public.users.last_name,
  public.users.birthday,
  public.users.sex,
  public.user_jur.company_id,
  public.user_jur.organization,
  public.user_jur.bank,
  public.user_jur.jur_addr,
  public.user_jur.rs,
  public.user_jur.ks,
  public.user_jur.bik,
  public.user_jur.inn,
  public.user_jur.kpp,
  public.user_jur.okdp,
  public.user_jur.okpo,
  public.user_jur.okonh,
  public.user_jur.okved,
  public.user_jur.www
FROM
  public.user_jur
  RIGHT OUTER JOIN public.users_has_user_jur ON (public.user_jur.company_id = public.users_has_user_jur.company_id)
  RIGHT OUTER JOIN public.users ON (public.users_has_user_jur.id = public.users.id);

