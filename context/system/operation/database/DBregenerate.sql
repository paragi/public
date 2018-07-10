--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: error; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE error (
    file text,
    line numeric,
    stack jsonb,
    acknowledged boolean DEFAULT false,
    seriousness numeric NOT NULL,
    dump bytea,
    additional_values jsonb,
    message text NOT NULL,
    count numeric DEFAULT 1,
    "time" timestamp without time zone DEFAULT timezone('UTC'::text, now()),
    id integer NOT NULL
);


--
-- Name: error_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE error_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: error_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE error_id_seq OWNED BY error.id;


--
-- Name: event; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE event (
    event text NOT NULL,
    state text,
    reply text,
    error text,
    additional_values jsonb,
    "time" timestamp without time zone DEFAULT timezone('UTC'::text, now()) NOT NULL,
    origin text,
    command text,
    terminal text
);


--
-- Name: person; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE person (
    user_id text NOT NULL,
    name text NOT NULL,
    password text NOT NULL,
    e_mail text,
    trust numeric,
    additional_values jsonb,
    "time" timestamp without time zone DEFAULT timezone('UTC'::text, now()),
    salt text
);


--
-- Name: reaction; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE reaction (
    "time" timestamp without time zone DEFAULT timezone('UTC'::text, now()),
    event text NOT NULL,
    command text NOT NULL,
    trust numeric NOT NULL,
    additional_values jsonb,
    user_id text,
    terminal_id text NOT NULL
);


--
-- Name: terminal; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE terminal (
    tid text NOT NULL,
    email text,
    applicant text,
    location text,
    mobile boolean NOT NULL,
    additional_values jsonb,
    network_location text,
    max_hops numeric,
    http_agent text NOT NULL,
    default_user text,
    terminal_name text NOT NULL,
    http_languages text NOT NULL,
    usual_ip inet,
    trust_in_terminal numeric NOT NULL,
    "time" timestamp without time zone DEFAULT timezone('UTC'::text, now())
);


--
-- Name: timer; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE timer (
    additional_values jsonb,
    timexp text NOT NULL,
    command text NOT NULL,
    trust numeric NOT NULL,
    origin text NOT NULL,
    terminal_name text NOT NULL,
    user_id text,
    "time" timestamp without time zone DEFAULT timezone('UTC'::text, now()),
    active boolean DEFAULT true
);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY error ALTER COLUMN id SET DEFAULT nextval('error_id_seq'::regclass);


--
-- Name: error_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY error
    ADD CONSTRAINT error_pkey PRIMARY KEY (message);


--
-- Name: people_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY person
    ADD CONSTRAINT people_pkey PRIMARY KEY (user_id);


--
-- Name: reaction_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY reaction
    ADD CONSTRAINT reaction_pkey PRIMARY KEY (event);


--
-- Name: terminal_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY terminal
    ADD CONSTRAINT terminal_pkey PRIMARY KEY (tid);


--
-- Name: timer_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY timer
    ADD CONSTRAINT timer_pkey PRIMARY KEY (timexp, command);


--
-- PostgreSQL database dump complete
--

