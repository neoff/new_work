-- =============================================================================
-- Имя модели: LK
-- Создано: 27-10-2010 12:35:45
-- Версия модели: 1
-- =============================================================================

CREATE TABLE "users" (
	"id" SERIAL NOT NULL,
	"email" varchar(100) NOT NULL,
	"password" varchar(100),
  PRIMARY KEY("id")
);


CREATE TABLE "user_person" (
	"id" int4 NOT NULL,
	"default_addtess" int4,
	"first_name" varchar(100),
	"midle_name" varchar(100),
	"last_name" varchar(100),
	"phone" int4,
	"mobile" int4,
  PRIMARY KEY("id")
);


COMMENT ON COLUMN "user_person"."default_addtess" IS 'адреса по умолчанию может и не быть';

CREATE TABLE "user_address" (
	"id" int4 NOT NULL,
	"city" int4 NOT NULL,
	"metro" int4 NOT NULL,
	"street" varchar,
	"house" varchar,
	"apartment" varchar,
	"phone" int4,
	"sort" int4,
  PRIMARY KEY("id")
);


CREATE UNIQUE INDEX "user_address_id_index" ON "user_address" USING BTREE (
	"id"
);


CREATE TABLE "metro" (
	"id" int4 NOT NULL,
	"name" varchar(100) NOT NULL,
	"delivery" int4,
  PRIMARY KEY("id")
);


CREATE TABLE "city" (
	"id" int4 NOT NULL,
	"name" varchar(100) NOT NULL,
	"delivery" int4,
  PRIMARY KEY("id")
);


CREATE TABLE "user_session" (
	"id" int4 NOT NULL,
	"session_data_id" int4,
	"name" varchar(50) NOT NULL,
  PRIMARY KEY("id")
);


CREATE TABLE "user_siebel" (
	"id" SERIAL NOT NULL,
	"session_id" int4 NOT NULL,
	"siebel_id" int4 NOT NULL,
  PRIMARY KEY("id","session_id","siebel_id")
);


CREATE TABLE "session_data" (
	"id" SERIAL NOT NULL,
	"data" text,
  PRIMARY KEY("id")
);


CREATE UNIQUE INDEX "session_index" ON "session_data" USING BTREE (
	"id"
);


CREATE TABLE "user_yur_address" (
	"id" int4 NOT NULL,
	"name" varchar(255),
	"bic" int4,
	"inn" int4,
  PRIMARY KEY("id")
)
INHERITS ("user_address");



ALTER TABLE "user_person" ADD CONSTRAINT "Ref_user_person_to_users" FOREIGN KEY ("id")
	REFERENCES "users"("id")
	MATCH SIMPLE
	ON DELETE CASCADE
	ON UPDATE CASCADE
	NOT DEFERRABLE;

ALTER TABLE "user_person" ADD CONSTRAINT "Ref_user_person_to_user_address" FOREIGN KEY ("default_addtess")
	REFERENCES "user_address"("id")
	MATCH SIMPLE
	ON DELETE SET NULL
	ON UPDATE CASCADE
	NOT DEFERRABLE;

ALTER TABLE "user_address" ADD CONSTRAINT "Ref_user_adress_to_users" FOREIGN KEY ("id")
	REFERENCES "users"("id")
	MATCH SIMPLE
	ON DELETE CASCADE
	ON UPDATE CASCADE
	NOT DEFERRABLE;

ALTER TABLE "user_address" ADD CONSTRAINT "Ref_user_address_to_city" FOREIGN KEY ("city")
	REFERENCES "city"("id")
	MATCH SIMPLE
	ON DELETE CASCADE
	ON UPDATE CASCADE
	NOT DEFERRABLE;

ALTER TABLE "user_address" ADD CONSTRAINT "Ref_user_address_to_metro" FOREIGN KEY ("metro")
	REFERENCES "metro"("id")
	MATCH SIMPLE
	ON DELETE CASCADE
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
	ON DELETE NO ACTION
	ON UPDATE NO ACTION
	NOT DEFERRABLE;

ALTER TABLE "user_siebel" ADD CONSTRAINT "Ref_user_siebel_to_user_session" FOREIGN KEY ("session_id")
	REFERENCES "user_session"("id")
	MATCH SIMPLE
	ON DELETE CASCADE
	ON UPDATE CASCADE
	NOT DEFERRABLE;

ALTER TABLE "user_yur_address" ADD CONSTRAINT "Ref_user_yur_address_to_user_address" FOREIGN KEY ("id")
	REFERENCES "user_address"("id")
	MATCH SIMPLE
	ON DELETE CASCADE
	ON UPDATE CASCADE
	NOT DEFERRABLE;


