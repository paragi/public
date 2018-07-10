--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: event; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE event (
    name character varying,
    "time" timestamp without time zone
);


ALTER TABLE event OWNER TO postgres;

--
-- Data for Name: event; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY event (name, "time") FROM stdin;
\.


--
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;
GRANT USAGE ON SCHEMA public TO "www-data";


--
-- Name: event; Type: ACL; Schema: public; Owner: postgres
--

REVOKE ALL ON TABLE event FROM PUBLIC;
REVOKE ALL ON TABLE event FROM postgres;
GRANT ALL ON TABLE event TO postgres;
GRANT SELECT ON TABLE event TO "www-data";


--
-- PostgreSQL database dump complete
--

