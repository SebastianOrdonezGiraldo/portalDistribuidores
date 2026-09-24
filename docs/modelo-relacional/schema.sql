--
-- Portal de Distribuidores — Import Corporal Medical SAS
-- DDL completa del modelo relacional (PostgreSQL)
--
-- Generado con:
--   createdb portal_ddl_tmp
--   DB_DATABASE=portal_ddl_tmp php artisan migrate --force
--   pg_dump --schema-only --no-owner --no-privileges --no-comments
--
-- Fuente de verdad: las 70 migraciones en database/migrations/.
-- Este archivo es el resultado consolidado de aplicarlas todas en orden.
-- Regenerar tras cada migracion nueva; no editar a mano.
--
-- 37 tablas | 37 PRIMARY KEY | 46 FOREIGN KEY | 99 indices
--

--
-- PostgreSQL database dump
--

\restrict a8LVd09EMNbpKQ85Xlb1Z3xsZbadP47I4X8yzqQNQOtq9aGoRUW927rCoMEHoQr

-- Dumped from database version 18.6
-- Dumped by pg_dump version 18.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: unaccent; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS unaccent WITH SCHEMA public;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cart_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cart_items (
    id bigint NOT NULL,
    cart_id bigint NOT NULL,
    product_id bigint NOT NULL,
    product_variant_id bigint,
    line_key character varying(255) NOT NULL,
    qty integer NOT NULL,
    unit_label character varying(40) DEFAULT 'unidades'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: cart_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cart_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cart_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cart_items_id_seq OWNED BY public.cart_items.id;


--
-- Name: carts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.carts (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    reminder_sent_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: carts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.carts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: carts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.carts_id_seq OWNED BY public.carts.id;


--
-- Name: catalog_banners; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.catalog_banners (
    id bigint NOT NULL,
    title character varying(120) NOT NULL,
    path character varying(255) NOT NULL,
    image_width integer,
    image_height integer,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    placement character varying(32) DEFAULT 'catalog'::character varying NOT NULL
);


--
-- Name: catalog_banners_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.catalog_banners_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: catalog_banners_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.catalog_banners_id_seq OWNED BY public.catalog_banners.id;


--
-- Name: categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.categories (
    id bigint NOT NULL,
    parent_id bigint,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;


--
-- Name: category_synonyms; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.category_synonyms (
    id bigint NOT NULL,
    category_id bigint NOT NULL,
    term character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: category_synonyms_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.category_synonyms_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: category_synonyms_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.category_synonyms_id_seq OWNED BY public.category_synonyms.id;


--
-- Name: commerce_pricing_rules; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.commerce_pricing_rules (
    id bigint NOT NULL,
    silver_markup_basis_points integer NOT NULL,
    silver_rounding_multiple integer NOT NULL,
    created_by_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    silver_min_order_enabled boolean DEFAULT false NOT NULL,
    silver_min_order_amount integer DEFAULT 1000000 NOT NULL,
    gold_min_order_enabled boolean DEFAULT false NOT NULL,
    gold_min_order_amount integer DEFAULT 1000000 NOT NULL,
    gold_pricing_threshold_enabled boolean DEFAULT false NOT NULL,
    gold_pricing_threshold_amount integer DEFAULT 1000000 NOT NULL,
    gold_pricing_threshold_basis character varying(32) DEFAULT 'gold_candidate'::character varying NOT NULL
);


--
-- Name: commerce_pricing_rules_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.commerce_pricing_rules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: commerce_pricing_rules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.commerce_pricing_rules_id_seq OWNED BY public.commerce_pricing_rules.id;


--
-- Name: commerce_tier_advisors; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.commerce_tier_advisors (
    id bigint NOT NULL,
    tier character varying(16) NOT NULL,
    advisor_name character varying(120) NOT NULL,
    advisor_email character varying(120) NOT NULL,
    advisor_whatsapp character varying(15) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: commerce_tier_advisors_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.commerce_tier_advisors_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: commerce_tier_advisors_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.commerce_tier_advisors_id_seq OWNED BY public.commerce_tier_advisors.id;


--
-- Name: company_branches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.company_branches (
    id bigint NOT NULL,
    distributor_id bigint NOT NULL,
    name character varying(120) NOT NULL,
    address character varying(180),
    city character varying(120),
    is_default boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: company_branches_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.company_branches_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: company_branches_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.company_branches_id_seq OWNED BY public.company_branches.id;


--
-- Name: company_list_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.company_list_items (
    id bigint NOT NULL,
    list_id bigint NOT NULL,
    product_id bigint,
    product_variant_id bigint,
    product_name_snapshot character varying(255) NOT NULL,
    sku_snapshot character varying(255) NOT NULL,
    variant_value_snapshot character varying(255),
    qty smallint DEFAULT '1'::smallint NOT NULL,
    unit_label character varying(40) DEFAULT 'unidad'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: company_list_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.company_list_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: company_list_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.company_list_items_id_seq OWNED BY public.company_list_items.id;


--
-- Name: company_lists; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.company_lists (
    id bigint NOT NULL,
    distributor_id bigint NOT NULL,
    created_by bigint,
    name character varying(120) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: company_lists_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.company_lists_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: company_lists_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.company_lists_id_seq OWNED BY public.company_lists.id;


--
-- Name: contapyme_inventory_mappings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contapyme_inventory_mappings (
    id bigint NOT NULL,
    product_id bigint,
    product_variant_id bigint,
    irecurso character varying(160) NOT NULL,
    status character varying(32) DEFAULT 'pending'::character varying NOT NULL,
    last_validated_at timestamp(0) without time zone,
    last_seen_at timestamp(0) without time zone,
    last_error text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: contapyme_inventory_mappings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contapyme_inventory_mappings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contapyme_inventory_mappings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contapyme_inventory_mappings_id_seq OWNED BY public.contapyme_inventory_mappings.id;


--
-- Name: contapyme_sync_runs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contapyme_sync_runs (
    id uuid NOT NULL,
    origin character varying(24) DEFAULT 'manual'::character varying NOT NULL,
    mode character varying(24) DEFAULT 'full'::character varying NOT NULL,
    status character varying(24) DEFAULT 'queued'::character varying NOT NULL,
    warehouse character varying(80),
    started_at timestamp(0) without time zone,
    finished_at timestamp(0) without time zone,
    duration_ms integer,
    processed integer DEFAULT 0 NOT NULL,
    updated integer DEFAULT 0 NOT NULL,
    unchanged integer DEFAULT 0 NOT NULL,
    no_sku integer DEFAULT 0 NOT NULL,
    confirmed_zero integer DEFAULT 0 NOT NULL,
    missing_contapyme integer DEFAULT 0 NOT NULL,
    unmapped integer DEFAULT 0 NOT NULL,
    skipped_variants integer DEFAULT 0 NOT NULL,
    failed integer DEFAULT 0 NOT NULL,
    summary text,
    error_groups json,
    error_details json,
    diagnostics json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: distributors; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.distributors (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    nit character varying(40),
    address character varying(180),
    city character varying(120),
    phone character varying(40),
    contact_email character varying(160),
    contact_name character varying(120),
    portal_tour_completed_at timestamp(0) without time zone,
    tier character varying(255) DEFAULT 'plata'::character varying NOT NULL,
    tier_changed_at timestamp(0) without time zone,
    tier_changed_by_id bigint
);


--
-- Name: distributors_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.distributors_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: distributors_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.distributors_id_seq OWNED BY public.distributors.id;


--
-- Name: document_downloads; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.document_downloads (
    id bigint NOT NULL,
    distributor_id bigint NOT NULL,
    product_document_id bigint NOT NULL,
    downloaded_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: document_downloads_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.document_downloads_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: document_downloads_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.document_downloads_id_seq OWNED BY public.document_downloads.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: inventory_hold_events; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.inventory_hold_events (
    id bigint NOT NULL,
    inventory_hold_id bigint NOT NULL,
    order_id bigint NOT NULL,
    user_id bigint,
    action character varying(32) NOT NULL,
    previous_quantity numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    new_quantity numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    reason character varying(120),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: inventory_hold_events_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.inventory_hold_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: inventory_hold_events_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.inventory_hold_events_id_seq OWNED BY public.inventory_hold_events.id;


--
-- Name: inventory_holds; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.inventory_holds (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    product_id bigint NOT NULL,
    product_variant_id bigint,
    inventory_key character varying(64) NOT NULL,
    quantity numeric(12,2) NOT NULL,
    status character varying(16) DEFAULT 'active'::character varying NOT NULL,
    released_at timestamp(0) without time zone,
    release_reason character varying(120),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: inventory_holds_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.inventory_holds_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: inventory_holds_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.inventory_holds_id_seq OWNED BY public.inventory_holds.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: order_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.order_items (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    product_id bigint,
    product_name_snapshot character varying(255) NOT NULL,
    sku_snapshot character varying(255) NOT NULL,
    qty numeric(12,2) NOT NULL,
    unit_label character varying(255) DEFAULT 'unidad'::character varying NOT NULL,
    price_each numeric(12,2) NOT NULL,
    subtotal numeric(14,2) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    product_variant_id bigint,
    variant_attribute_snapshot character varying(120),
    variant_value_snapshot character varying(120),
    is_vat_excluded_snapshot boolean DEFAULT false NOT NULL,
    vat_rate_snapshot numeric(5,4) DEFAULT 0.1900 NOT NULL,
    base_unit_price numeric(14,2),
    silver_unit_price numeric(14,2),
    unit_savings numeric(14,2),
    line_savings numeric(14,2)
);


--
-- Name: order_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.order_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: order_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.order_items_id_seq OWNED BY public.order_items.id;


--
-- Name: order_status_histories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.order_status_histories (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    from_status character varying(255),
    to_status character varying(255) NOT NULL,
    changed_by_user_id bigint,
    note character varying(500),
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: order_status_histories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.order_status_histories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: order_status_histories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.order_status_histories_id_seq OWNED BY public.order_status_histories.id;


--
-- Name: orders; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.orders (
    id bigint NOT NULL,
    distributor_id bigint,
    user_id bigint,
    oc_number character varying(255) NOT NULL,
    contact_name character varying(255) NOT NULL,
    company_name character varying(255) NOT NULL,
    phone character varying(255),
    notes text,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    total_amount numeric(14,2) DEFAULT '0'::numeric NOT NULL,
    pdf_path character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    company_nit character varying(40),
    company_address character varying(180),
    contact_email character varying(120),
    city character varying(120),
    approval_note character varying(500),
    department character varying(120),
    tracking_number character varying(80),
    shipping_carrier character varying(40),
    distributor_tier_snapshot character varying(255),
    payment_status character varying(32) DEFAULT 'not_applicable'::character varying NOT NULL,
    payment_method character varying(32),
    payment_receipt_path character varying(255),
    payment_receipt_filename character varying(255),
    payment_receipt_uploaded_at timestamp(0) without time zone,
    payment_reservation_expires_at timestamp(0) without time zone,
    commerce_pricing_rule_id bigint,
    minimum_order_tier_snapshot character varying(16),
    minimum_order_enabled_snapshot boolean,
    minimum_order_amount_snapshot integer,
    minimum_order_evaluated_amount numeric(14,2),
    minimum_order_reached boolean,
    minimum_order_decision_reason_snapshot character varying(64),
    gold_pricing_threshold_enabled_snapshot boolean,
    gold_pricing_threshold_amount_snapshot integer,
    gold_pricing_threshold_basis_snapshot character varying(32),
    gold_pricing_decision_reason_snapshot character varying(64),
    gold_pricing_applied boolean,
    silver_candidate_total numeric(14,2),
    gold_candidate_total numeric(14,2),
    gold_savings_total numeric(14,2),
    inventory_reconciliation_status character varying(32),
    inventory_reconciliation_attempted_at timestamp(0) without time zone,
    inventory_reconciled_at timestamp(0) without time zone,
    inventory_reconciliation_error text,
    advisor_name_snapshot character varying(120),
    advisor_email_snapshot character varying(120),
    advisor_whatsapp_snapshot character varying(15)
);


--
-- Name: orders_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.orders_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: orders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.orders_id_seq OWNED BY public.orders.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: payment_upload_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payment_upload_tokens (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    token_hash character varying(64) NOT NULL,
    expires_at timestamp(0) without time zone NOT NULL,
    consumed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: payment_upload_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.payment_upload_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: payment_upload_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.payment_upload_tokens_id_seq OWNED BY public.payment_upload_tokens.id;


--
-- Name: product_attribute_values; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_attribute_values (
    id bigint NOT NULL,
    product_attribute_id bigint NOT NULL,
    value character varying(120) NOT NULL,
    slug character varying(140) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: product_attribute_values_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_attribute_values_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_attribute_values_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_attribute_values_id_seq OWNED BY public.product_attribute_values.id;


--
-- Name: product_attributes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_attributes (
    id bigint NOT NULL,
    name character varying(120) NOT NULL,
    slug character varying(140) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: product_attributes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_attributes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_attributes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_attributes_id_seq OWNED BY public.product_attributes.id;


--
-- Name: product_documents; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_documents (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    type character varying(255) DEFAULT 'tech_sheet'::character varying NOT NULL,
    path character varying(255) NOT NULL,
    filename character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    sort_order smallint DEFAULT '0'::smallint NOT NULL
);


--
-- Name: product_documents_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_documents_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_documents_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_documents_id_seq OWNED BY public.product_documents.id;


--
-- Name: product_photos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_photos (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    path character varying(255) NOT NULL,
    is_primary boolean DEFAULT false NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    photo_width integer,
    photo_height integer
);


--
-- Name: product_photos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_photos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_photos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_photos_id_seq OWNED BY public.product_photos.id;


--
-- Name: product_variants; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_variants (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    product_attribute_value_id bigint NOT NULL,
    price numeric(12,2) NOT NULL,
    stock numeric(12,2),
    is_active boolean DEFAULT true NOT NULL,
    sort_order smallint DEFAULT '1'::smallint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    stock_synced_at timestamp(0) without time zone,
    stock_sync_status character varying(32),
    reserved_stock numeric(12,2) DEFAULT '0'::numeric NOT NULL
);


--
-- Name: product_variants_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_variants_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_variants_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_variants_id_seq OWNED BY public.product_variants.id;


--
-- Name: product_videos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_videos (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    url character varying(255) NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: product_videos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_videos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: product_videos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_videos_id_seq OWNED BY public.product_videos.id;


--
-- Name: products; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.products (
    id bigint NOT NULL,
    category_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    sku character varying(255) NOT NULL,
    description text,
    price numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    stock numeric(12,2),
    brand character varying(120),
    variant_attribute_id bigint,
    is_vat_excluded boolean DEFAULT false NOT NULL,
    stock_synced_at timestamp(0) without time zone,
    stock_sync_status character varying(32),
    is_new boolean DEFAULT false NOT NULL,
    new_until date,
    reserved_stock numeric(12,2) DEFAULT '0'::numeric NOT NULL
);


--
-- Name: products_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.products_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.products_id_seq OWNED BY public.products.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: stock_movements; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.stock_movements (
    id bigint NOT NULL,
    product_id bigint,
    product_variant_id bigint,
    order_id bigint,
    user_id bigint,
    source character varying(255) NOT NULL,
    previous_stock numeric(12,2),
    new_stock numeric(12,2),
    delta numeric(12,2),
    order_quantity numeric(12,2),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: stock_movements_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.stock_movements_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: stock_movements_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.stock_movements_id_seq OWNED BY public.stock_movements.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    role character varying(255) DEFAULT 'distributor'::character varying NOT NULL,
    distributor_id bigint,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    is_active boolean DEFAULT true NOT NULL,
    email_verification_code character varying(255),
    email_verification_code_expires_at timestamp(0) without time zone
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: cart_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cart_items ALTER COLUMN id SET DEFAULT nextval('public.cart_items_id_seq'::regclass);


--
-- Name: carts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.carts ALTER COLUMN id SET DEFAULT nextval('public.carts_id_seq'::regclass);


--
-- Name: catalog_banners id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.catalog_banners ALTER COLUMN id SET DEFAULT nextval('public.catalog_banners_id_seq'::regclass);


--
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- Name: category_synonyms id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.category_synonyms ALTER COLUMN id SET DEFAULT nextval('public.category_synonyms_id_seq'::regclass);


--
-- Name: commerce_pricing_rules id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.commerce_pricing_rules ALTER COLUMN id SET DEFAULT nextval('public.commerce_pricing_rules_id_seq'::regclass);


--
-- Name: commerce_tier_advisors id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.commerce_tier_advisors ALTER COLUMN id SET DEFAULT nextval('public.commerce_tier_advisors_id_seq'::regclass);


--
-- Name: company_branches id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_branches ALTER COLUMN id SET DEFAULT nextval('public.company_branches_id_seq'::regclass);


--
-- Name: company_list_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_list_items ALTER COLUMN id SET DEFAULT nextval('public.company_list_items_id_seq'::regclass);


--
-- Name: company_lists id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_lists ALTER COLUMN id SET DEFAULT nextval('public.company_lists_id_seq'::regclass);


--
-- Name: contapyme_inventory_mappings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contapyme_inventory_mappings ALTER COLUMN id SET DEFAULT nextval('public.contapyme_inventory_mappings_id_seq'::regclass);


--
-- Name: distributors id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.distributors ALTER COLUMN id SET DEFAULT nextval('public.distributors_id_seq'::regclass);


--
-- Name: document_downloads id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_downloads ALTER COLUMN id SET DEFAULT nextval('public.document_downloads_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: inventory_hold_events id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_hold_events ALTER COLUMN id SET DEFAULT nextval('public.inventory_hold_events_id_seq'::regclass);


--
-- Name: inventory_holds id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_holds ALTER COLUMN id SET DEFAULT nextval('public.inventory_holds_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: order_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_items ALTER COLUMN id SET DEFAULT nextval('public.order_items_id_seq'::regclass);


--
-- Name: order_status_histories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_status_histories ALTER COLUMN id SET DEFAULT nextval('public.order_status_histories_id_seq'::regclass);


--
-- Name: orders id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders ALTER COLUMN id SET DEFAULT nextval('public.orders_id_seq'::regclass);


--
-- Name: payment_upload_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_upload_tokens ALTER COLUMN id SET DEFAULT nextval('public.payment_upload_tokens_id_seq'::regclass);


--
-- Name: product_attribute_values id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_attribute_values ALTER COLUMN id SET DEFAULT nextval('public.product_attribute_values_id_seq'::regclass);


--
-- Name: product_attributes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_attributes ALTER COLUMN id SET DEFAULT nextval('public.product_attributes_id_seq'::regclass);


--
-- Name: product_documents id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_documents ALTER COLUMN id SET DEFAULT nextval('public.product_documents_id_seq'::regclass);


--
-- Name: product_photos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_photos ALTER COLUMN id SET DEFAULT nextval('public.product_photos_id_seq'::regclass);


--
-- Name: product_variants id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variants ALTER COLUMN id SET DEFAULT nextval('public.product_variants_id_seq'::regclass);


--
-- Name: product_videos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_videos ALTER COLUMN id SET DEFAULT nextval('public.product_videos_id_seq'::regclass);


--
-- Name: products id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products ALTER COLUMN id SET DEFAULT nextval('public.products_id_seq'::regclass);


--
-- Name: stock_movements id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_movements ALTER COLUMN id SET DEFAULT nextval('public.stock_movements_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: cart_items cart_items_cart_id_line_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cart_items
    ADD CONSTRAINT cart_items_cart_id_line_key_unique UNIQUE (cart_id, line_key);


--
-- Name: cart_items cart_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cart_items
    ADD CONSTRAINT cart_items_pkey PRIMARY KEY (id);


--
-- Name: carts carts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.carts
    ADD CONSTRAINT carts_pkey PRIMARY KEY (id);


--
-- Name: carts carts_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.carts
    ADD CONSTRAINT carts_user_id_unique UNIQUE (user_id);


--
-- Name: catalog_banners catalog_banners_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.catalog_banners
    ADD CONSTRAINT catalog_banners_pkey PRIMARY KEY (id);


--
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);


--
-- Name: categories categories_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_slug_unique UNIQUE (slug);


--
-- Name: category_synonyms category_synonyms_category_id_term_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.category_synonyms
    ADD CONSTRAINT category_synonyms_category_id_term_unique UNIQUE (category_id, term);


--
-- Name: category_synonyms category_synonyms_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.category_synonyms
    ADD CONSTRAINT category_synonyms_pkey PRIMARY KEY (id);


--
-- Name: commerce_pricing_rules commerce_pricing_rules_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.commerce_pricing_rules
    ADD CONSTRAINT commerce_pricing_rules_pkey PRIMARY KEY (id);


--
-- Name: commerce_tier_advisors commerce_tier_advisors_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.commerce_tier_advisors
    ADD CONSTRAINT commerce_tier_advisors_pkey PRIMARY KEY (id);


--
-- Name: commerce_tier_advisors commerce_tier_advisors_tier_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.commerce_tier_advisors
    ADD CONSTRAINT commerce_tier_advisors_tier_unique UNIQUE (tier);


--
-- Name: company_branches company_branches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_branches
    ADD CONSTRAINT company_branches_pkey PRIMARY KEY (id);


--
-- Name: company_list_items company_list_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_list_items
    ADD CONSTRAINT company_list_items_pkey PRIMARY KEY (id);


--
-- Name: company_lists company_lists_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_lists
    ADD CONSTRAINT company_lists_pkey PRIMARY KEY (id);


--
-- Name: contapyme_inventory_mappings contapyme_inventory_mappings_irecurso_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contapyme_inventory_mappings
    ADD CONSTRAINT contapyme_inventory_mappings_irecurso_unique UNIQUE (irecurso);


--
-- Name: contapyme_inventory_mappings contapyme_inventory_mappings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contapyme_inventory_mappings
    ADD CONSTRAINT contapyme_inventory_mappings_pkey PRIMARY KEY (id);


--
-- Name: contapyme_inventory_mappings contapyme_mapping_product_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contapyme_inventory_mappings
    ADD CONSTRAINT contapyme_mapping_product_unique UNIQUE (product_id);


--
-- Name: contapyme_inventory_mappings contapyme_mapping_variant_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contapyme_inventory_mappings
    ADD CONSTRAINT contapyme_mapping_variant_unique UNIQUE (product_variant_id);


--
-- Name: contapyme_sync_runs contapyme_sync_runs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contapyme_sync_runs
    ADD CONSTRAINT contapyme_sync_runs_pkey PRIMARY KEY (id);


--
-- Name: distributors distributors_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.distributors
    ADD CONSTRAINT distributors_pkey PRIMARY KEY (id);


--
-- Name: document_downloads document_downloads_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_downloads
    ADD CONSTRAINT document_downloads_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: inventory_hold_events inventory_hold_events_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_hold_events
    ADD CONSTRAINT inventory_hold_events_pkey PRIMARY KEY (id);


--
-- Name: inventory_holds inventory_holds_order_inventory_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_holds
    ADD CONSTRAINT inventory_holds_order_inventory_unique UNIQUE (order_id, inventory_key);


--
-- Name: inventory_holds inventory_holds_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_holds
    ADD CONSTRAINT inventory_holds_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: order_items order_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_items
    ADD CONSTRAINT order_items_pkey PRIMARY KEY (id);


--
-- Name: order_status_histories order_status_histories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_status_histories
    ADD CONSTRAINT order_status_histories_pkey PRIMARY KEY (id);


--
-- Name: orders orders_oc_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_oc_number_unique UNIQUE (oc_number);


--
-- Name: orders orders_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: payment_upload_tokens payment_upload_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_upload_tokens
    ADD CONSTRAINT payment_upload_tokens_pkey PRIMARY KEY (id);


--
-- Name: payment_upload_tokens payment_upload_tokens_token_hash_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_upload_tokens
    ADD CONSTRAINT payment_upload_tokens_token_hash_unique UNIQUE (token_hash);


--
-- Name: product_attribute_values product_attribute_values_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_attribute_values
    ADD CONSTRAINT product_attribute_values_pkey PRIMARY KEY (id);


--
-- Name: product_attribute_values product_attribute_values_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_attribute_values
    ADD CONSTRAINT product_attribute_values_unique UNIQUE (product_attribute_id, slug);


--
-- Name: product_attributes product_attributes_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_attributes
    ADD CONSTRAINT product_attributes_name_unique UNIQUE (name);


--
-- Name: product_attributes product_attributes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_attributes
    ADD CONSTRAINT product_attributes_pkey PRIMARY KEY (id);


--
-- Name: product_attributes product_attributes_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_attributes
    ADD CONSTRAINT product_attributes_slug_unique UNIQUE (slug);


--
-- Name: product_documents product_documents_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_documents
    ADD CONSTRAINT product_documents_pkey PRIMARY KEY (id);


--
-- Name: product_documents product_documents_product_id_type_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_documents
    ADD CONSTRAINT product_documents_product_id_type_unique UNIQUE (product_id, type);


--
-- Name: product_photos product_photos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_photos
    ADD CONSTRAINT product_photos_pkey PRIMARY KEY (id);


--
-- Name: product_variants product_variants_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variants
    ADD CONSTRAINT product_variants_pkey PRIMARY KEY (id);


--
-- Name: product_variants product_variants_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variants
    ADD CONSTRAINT product_variants_unique UNIQUE (product_id, product_attribute_value_id);


--
-- Name: product_videos product_videos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_videos
    ADD CONSTRAINT product_videos_pkey PRIMARY KEY (id);


--
-- Name: products products_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_pkey PRIMARY KEY (id);


--
-- Name: products products_sku_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_sku_unique UNIQUE (sku);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: stock_movements stock_movements_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_pkey PRIMARY KEY (id);


--
-- Name: users users_distributor_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_distributor_id_unique UNIQUE (distributor_id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: cart_items_cart_id_product_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cart_items_cart_id_product_id_index ON public.cart_items USING btree (cart_id, product_id);


--
-- Name: carts_reminder_sent_at_updated_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX carts_reminder_sent_at_updated_at_index ON public.carts USING btree (reminder_sent_at, updated_at);


--
-- Name: catalog_banners_placement_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX catalog_banners_placement_index ON public.catalog_banners USING btree (placement);


--
-- Name: categories_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX categories_is_active_index ON public.categories USING btree (is_active);


--
-- Name: company_branches_distributor_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX company_branches_distributor_id_index ON public.company_branches USING btree (distributor_id);


--
-- Name: company_list_items_list_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX company_list_items_list_id_index ON public.company_list_items USING btree (list_id);


--
-- Name: company_lists_distributor_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX company_lists_distributor_id_index ON public.company_lists USING btree (distributor_id);


--
-- Name: contapyme_inventory_mappings_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contapyme_inventory_mappings_status_index ON public.contapyme_inventory_mappings USING btree (status);


--
-- Name: contapyme_sync_runs_origin_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contapyme_sync_runs_origin_index ON public.contapyme_sync_runs USING btree (origin);


--
-- Name: contapyme_sync_runs_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contapyme_sync_runs_status_index ON public.contapyme_sync_runs USING btree (status);


--
-- Name: distributors_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX distributors_status_index ON public.distributors USING btree (status);


--
-- Name: distributors_tier_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX distributors_tier_index ON public.distributors USING btree (tier);


--
-- Name: document_downloads_lookup_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX document_downloads_lookup_idx ON public.document_downloads USING btree (distributor_id, product_document_id, downloaded_at);


--
-- Name: inventory_hold_events_order_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX inventory_hold_events_order_id_created_at_index ON public.inventory_hold_events USING btree (order_id, created_at);


--
-- Name: inventory_holds_product_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX inventory_holds_product_id_status_index ON public.inventory_holds USING btree (product_id, status);


--
-- Name: inventory_holds_product_variant_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX inventory_holds_product_variant_id_status_index ON public.inventory_holds USING btree (product_variant_id, status);


--
-- Name: inventory_holds_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX inventory_holds_status_index ON public.inventory_holds USING btree (status);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: order_status_histories_from_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX order_status_histories_from_status_index ON public.order_status_histories USING btree (from_status);


--
-- Name: order_status_histories_order_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX order_status_histories_order_id_created_at_index ON public.order_status_histories USING btree (order_id, created_at);


--
-- Name: order_status_histories_to_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX order_status_histories_to_status_index ON public.order_status_histories USING btree (to_status);


--
-- Name: orders_distributor_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX orders_distributor_id_status_index ON public.orders USING btree (distributor_id, status);


--
-- Name: orders_inventory_reconciliation_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX orders_inventory_reconciliation_status_index ON public.orders USING btree (inventory_reconciliation_status);


--
-- Name: orders_payment_status_expires_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX orders_payment_status_expires_idx ON public.orders USING btree (payment_status, payment_reservation_expires_at);


--
-- Name: orders_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX orders_status_index ON public.orders USING btree (status);


--
-- Name: payment_upload_tokens_order_id_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payment_upload_tokens_order_id_expires_at_index ON public.payment_upload_tokens USING btree (order_id, expires_at);


--
-- Name: product_documents_product_id_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX product_documents_product_id_type_index ON public.product_documents USING btree (product_id, type);


--
-- Name: product_photos_product_id_is_primary_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX product_photos_product_id_is_primary_index ON public.product_photos USING btree (product_id, is_primary);


--
-- Name: product_variants_product_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX product_variants_product_id_is_active_index ON public.product_variants USING btree (product_id, is_active);


--
-- Name: product_variants_stock_sync_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX product_variants_stock_sync_status_index ON public.product_variants USING btree (stock_sync_status);


--
-- Name: products_category_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_category_id_is_active_index ON public.products USING btree (category_id, is_active);


--
-- Name: products_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_is_active_index ON public.products USING btree (is_active);


--
-- Name: products_stock_sync_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX products_stock_sync_status_index ON public.products USING btree (stock_sync_status);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: stock_movements_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX stock_movements_created_at_index ON public.stock_movements USING btree (created_at);


--
-- Name: stock_movements_product_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX stock_movements_product_id_created_at_index ON public.stock_movements USING btree (product_id, created_at);


--
-- Name: stock_movements_source_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX stock_movements_source_index ON public.stock_movements USING btree (source);


--
-- Name: users_distributor_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_distributor_id_index ON public.users USING btree (distributor_id);


--
-- Name: users_role_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_role_index ON public.users USING btree (role);


--
-- Name: cart_items cart_items_cart_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cart_items
    ADD CONSTRAINT cart_items_cart_id_foreign FOREIGN KEY (cart_id) REFERENCES public.carts(id) ON DELETE CASCADE;


--
-- Name: cart_items cart_items_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cart_items
    ADD CONSTRAINT cart_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: cart_items cart_items_product_variant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cart_items
    ADD CONSTRAINT cart_items_product_variant_id_foreign FOREIGN KEY (product_variant_id) REFERENCES public.product_variants(id) ON DELETE CASCADE;


--
-- Name: carts carts_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.carts
    ADD CONSTRAINT carts_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: categories categories_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.categories(id) ON DELETE SET NULL;


--
-- Name: category_synonyms category_synonyms_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.category_synonyms
    ADD CONSTRAINT category_synonyms_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE CASCADE;


--
-- Name: commerce_pricing_rules commerce_pricing_rules_created_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.commerce_pricing_rules
    ADD CONSTRAINT commerce_pricing_rules_created_by_id_foreign FOREIGN KEY (created_by_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: company_branches company_branches_distributor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_branches
    ADD CONSTRAINT company_branches_distributor_id_foreign FOREIGN KEY (distributor_id) REFERENCES public.distributors(id) ON DELETE CASCADE;


--
-- Name: company_list_items company_list_items_list_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_list_items
    ADD CONSTRAINT company_list_items_list_id_foreign FOREIGN KEY (list_id) REFERENCES public.company_lists(id) ON DELETE CASCADE;


--
-- Name: company_list_items company_list_items_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_list_items
    ADD CONSTRAINT company_list_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE SET NULL;


--
-- Name: company_list_items company_list_items_product_variant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_list_items
    ADD CONSTRAINT company_list_items_product_variant_id_foreign FOREIGN KEY (product_variant_id) REFERENCES public.product_variants(id) ON DELETE SET NULL;


--
-- Name: company_lists company_lists_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_lists
    ADD CONSTRAINT company_lists_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: company_lists company_lists_distributor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.company_lists
    ADD CONSTRAINT company_lists_distributor_id_foreign FOREIGN KEY (distributor_id) REFERENCES public.distributors(id) ON DELETE CASCADE;


--
-- Name: contapyme_inventory_mappings contapyme_inventory_mappings_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contapyme_inventory_mappings
    ADD CONSTRAINT contapyme_inventory_mappings_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: contapyme_inventory_mappings contapyme_inventory_mappings_product_variant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contapyme_inventory_mappings
    ADD CONSTRAINT contapyme_inventory_mappings_product_variant_id_foreign FOREIGN KEY (product_variant_id) REFERENCES public.product_variants(id) ON DELETE CASCADE;


--
-- Name: distributors distributors_tier_changed_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.distributors
    ADD CONSTRAINT distributors_tier_changed_by_id_foreign FOREIGN KEY (tier_changed_by_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: document_downloads document_downloads_distributor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_downloads
    ADD CONSTRAINT document_downloads_distributor_id_foreign FOREIGN KEY (distributor_id) REFERENCES public.distributors(id) ON DELETE CASCADE;


--
-- Name: document_downloads document_downloads_product_document_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_downloads
    ADD CONSTRAINT document_downloads_product_document_id_foreign FOREIGN KEY (product_document_id) REFERENCES public.product_documents(id) ON DELETE CASCADE;


--
-- Name: inventory_hold_events inventory_hold_events_inventory_hold_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_hold_events
    ADD CONSTRAINT inventory_hold_events_inventory_hold_id_foreign FOREIGN KEY (inventory_hold_id) REFERENCES public.inventory_holds(id) ON DELETE CASCADE;


--
-- Name: inventory_hold_events inventory_hold_events_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_hold_events
    ADD CONSTRAINT inventory_hold_events_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: inventory_hold_events inventory_hold_events_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_hold_events
    ADD CONSTRAINT inventory_hold_events_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: inventory_holds inventory_holds_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_holds
    ADD CONSTRAINT inventory_holds_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: inventory_holds inventory_holds_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_holds
    ADD CONSTRAINT inventory_holds_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE RESTRICT;


--
-- Name: inventory_holds inventory_holds_product_variant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory_holds
    ADD CONSTRAINT inventory_holds_product_variant_id_foreign FOREIGN KEY (product_variant_id) REFERENCES public.product_variants(id) ON DELETE RESTRICT;


--
-- Name: order_items order_items_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_items
    ADD CONSTRAINT order_items_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: order_items order_items_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_items
    ADD CONSTRAINT order_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE SET NULL;


--
-- Name: order_items order_items_product_variant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_items
    ADD CONSTRAINT order_items_product_variant_id_foreign FOREIGN KEY (product_variant_id) REFERENCES public.product_variants(id) ON DELETE SET NULL;


--
-- Name: order_status_histories order_status_histories_changed_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_status_histories
    ADD CONSTRAINT order_status_histories_changed_by_user_id_foreign FOREIGN KEY (changed_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: order_status_histories order_status_histories_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_status_histories
    ADD CONSTRAINT order_status_histories_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: orders orders_commerce_pricing_rule_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_commerce_pricing_rule_id_foreign FOREIGN KEY (commerce_pricing_rule_id) REFERENCES public.commerce_pricing_rules(id) ON DELETE RESTRICT;


--
-- Name: orders orders_distributor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_distributor_id_foreign FOREIGN KEY (distributor_id) REFERENCES public.distributors(id) ON DELETE RESTRICT;


--
-- Name: orders orders_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: payment_upload_tokens payment_upload_tokens_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_upload_tokens
    ADD CONSTRAINT payment_upload_tokens_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: product_attribute_values product_attribute_values_product_attribute_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_attribute_values
    ADD CONSTRAINT product_attribute_values_product_attribute_id_foreign FOREIGN KEY (product_attribute_id) REFERENCES public.product_attributes(id) ON DELETE CASCADE;


--
-- Name: product_documents product_documents_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_documents
    ADD CONSTRAINT product_documents_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: product_photos product_photos_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_photos
    ADD CONSTRAINT product_photos_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: product_variants product_variants_product_attribute_value_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variants
    ADD CONSTRAINT product_variants_product_attribute_value_id_foreign FOREIGN KEY (product_attribute_value_id) REFERENCES public.product_attribute_values(id) ON DELETE RESTRICT;


--
-- Name: product_variants product_variants_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variants
    ADD CONSTRAINT product_variants_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: product_videos product_videos_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_videos
    ADD CONSTRAINT product_videos_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: products products_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE RESTRICT;


--
-- Name: products products_variant_attribute_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_variant_attribute_id_foreign FOREIGN KEY (variant_attribute_id) REFERENCES public.product_attributes(id) ON DELETE SET NULL;


--
-- Name: stock_movements stock_movements_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE SET NULL;


--
-- Name: stock_movements stock_movements_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE SET NULL;


--
-- Name: stock_movements stock_movements_product_variant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_product_variant_id_foreign FOREIGN KEY (product_variant_id) REFERENCES public.product_variants(id) ON DELETE SET NULL;


--
-- Name: stock_movements stock_movements_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: users users_distributor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_distributor_id_foreign FOREIGN KEY (distributor_id) REFERENCES public.distributors(id) ON DELETE SET NULL;


--
-- PostgreSQL database dump complete
--

\unrestrict a8LVd09EMNbpKQ85Xlb1Z3xsZbadP47I4X8yzqQNQOtq9aGoRUW927rCoMEHoQr

