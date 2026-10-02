--
-- PostgreSQL database dump
--

\restrict N2WRNTXSnnV8XHdznpUejDjA7h04ad00I58T6fUiJc7I0J4DiHLUPWfgfGhoe8K

-- Dumped from database version 18.4
-- Dumped by pg_dump version 18.4

-- Started on 2026-10-02 22:05:03

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
-- TOC entry 5273 (class 0 OID 56396)
-- Dependencies: 230
-- Data for Name: role_groups; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.role_groups VALUES (1, 'Developer', NULL, true, NULL, NULL, NULL, NULL);
INSERT INTO public.role_groups VALUES (2, 'System Admin', NULL, true, NULL, NULL, NULL, NULL);


--
-- TOC entry 5275 (class 0 OID 56410)
-- Dependencies: 232
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.roles VALUES (-1, 'developer', 'Developer', 1, 'Role untuk developer sistem', true, true, NULL, NULL, NULL, NULL);
INSERT INTO public.roles VALUES (1, 'super-admin', 'Super Admin', 2, NULL, true, true, NULL, NULL, NULL, NULL);


--
-- TOC entry 5277 (class 0 OID 56431)
-- Dependencies: 234
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.users VALUES (1, 'Developer', 'dev', '$2y$12$oJeDkVHbnkohpbQP/yAeq.gvGrlYQw5q9mfB77NqagAqO5zH2GoLG', 'demo.com', NULL, -1, NULL, NULL, NULL, NULL, 'user_active', NULL, NULL, NULL, NULL);


--
-- TOC entry 5286 (class 0 OID 56533)
-- Dependencies: 243
-- Data for Name: banners; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 5265 (class 0 OID 56326)
-- Dependencies: 222
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 5266 (class 0 OID 56336)
-- Dependencies: 223
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 5292 (class 0 OID 56619)
-- Dependencies: 249
-- Data for Name: events; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 5271 (class 0 OID 56377)
-- Dependencies: 228
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 5300 (class 0 OID 56749)
-- Dependencies: 257
-- Data for Name: feedbacks_categories; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 5302 (class 0 OID 56774)
-- Dependencies: 259
-- Data for Name: feedbacks; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 5304 (class 0 OID 56797)
-- Dependencies: 261
-- Data for Name: global_config; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.global_config VALUES (1, 'Berprestasi, berkarakter, dan siap menjadi generasi hebat Indonesia.', 'Profil Sekolah', '<p><strong style="color: rgb(0, 0, 0);">SMKN 7 Semarang</strong><span style="color: rgb(0, 0, 0);"> diresmikan pada tanggal 7 Juni 1971 oleh Presiden Republik Indonesia - Soeharto, dengan nama Proyek Perintis Sekolah Teknologi Menengah Pembangunan Semarang dengan lama pendidikan 4 (empat) tahun.</span></p><p><br></p><p><span style="color: rgb(0, 0, 0);">Dikenal dengan sebutan </span><strong style="color: rgb(0, 0, 0);">STEMBA</strong><span style="color: rgb(0, 0, 0);">, sekolah ini unggul dalam mencetak lulusan dengan keahlian praktis, sering kali memiliki ikatan kerja (penyaluran kerja) yang tinggi di industri.</span></p>', '/2026/202609/global_config/1790763553.png', '/2026/202609/global_config/1790763560.jpeg', 'Menjadi Sekolah Internasional Tahun 2030.', NULL, 'SMKN 7 Semarang', 'SMK Negeri 7 Semarang merupakan salah satu SMK unggulan di Jawa Tengah yang berfokus menciptakan lulusan berkualitas, berkarakter, dan siap kerja.', 'Tiada Hari Tanpa Prestasi', '(024) 8311532', 'admin@smkn7semarang.sch.id', 'https://www.instagram.com/smknegeri7semarang', 'https://www.youtube.com/@SMKNegeri7Semarang', 'https://web.facebook.com/smknegeri7semarang/', 'https://www.linkedin.com/school/smk-negeri-7-semarang/', NULL, 1, 1, '2026-09-30 17:18:19+07', '2026-10-01 14:08:03+07');


--
-- TOC entry 5269 (class 0 OID 56362)
-- Dependencies: 226
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 5268 (class 0 OID 56347)
-- Dependencies: 225
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 5288 (class 0 OID 56559)
-- Dependencies: 245
-- Data for Name: majors; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 5310 (class 0 OID 56888)
-- Dependencies: 267
-- Data for Name: major_competent; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 5312 (class 0 OID 56918)
-- Dependencies: 269
-- Data for Name: major_gallery; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 5279 (class 0 OID 56456)
-- Dependencies: 236
-- Data for Name: permissions; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 5281 (class 0 OID 56472)
-- Dependencies: 238
-- Data for Name: mapping_roles_permissions; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 5263 (class 0 OID 56308)
-- Dependencies: 220
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.migrations VALUES (1, '0001_01_01_000000_create_users_table', 1);
INSERT INTO public.migrations VALUES (2, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO public.migrations VALUES (3, '0001_01_01_000002_create_jobs_table', 1);
INSERT INTO public.migrations VALUES (4, '2021_05_01_061232_create_role_groups', 1);
INSERT INTO public.migrations VALUES (5, '2021_05_31_000001_create_roles', 1);
INSERT INTO public.migrations VALUES (6, '2021_05_31_000001_create_users_table', 1);
INSERT INTO public.migrations VALUES (7, '2021_06_01_062309_create_permissions', 1);
INSERT INTO public.migrations VALUES (8, '2021_06_01_062319_create_mapping_roles_permissions', 1);
INSERT INTO public.migrations VALUES (9, '2022_07_12_131716_create_user_verify_emails', 1);
INSERT INTO public.migrations VALUES (10, '2025_09_15_173010_create_sessions', 1);
INSERT INTO public.migrations VALUES (11, '2026_06_15_160300_create_banners', 1);
INSERT INTO public.migrations VALUES (12, '2026_06_15_163247_create_majors', 1);
INSERT INTO public.migrations VALUES (13, '2026_06_15_163325_create_missions', 1);
INSERT INTO public.migrations VALUES (14, '2026_06_15_163349_create_events', 1);
INSERT INTO public.migrations VALUES (15, '2026_06_15_163356_create_news_categories', 1);
INSERT INTO public.migrations VALUES (16, '2026_06_15_163358_create_news', 1);
INSERT INTO public.migrations VALUES (17, '2026_06_15_163412_create_votings', 1);
INSERT INTO public.migrations VALUES (18, '2026_06_15_163420_create_feedback_categories', 1);
INSERT INTO public.migrations VALUES (19, '2026_06_15_163421_create_feedbacks', 1);
INSERT INTO public.migrations VALUES (20, '2026_06_15_163437_create_global_config', 1);
INSERT INTO public.migrations VALUES (21, '2026_06_15_163500_create_voting_candidates', 1);
INSERT INTO public.migrations VALUES (22, '2026_06_15_163620_create_voting_logs', 1);
INSERT INTO public.migrations VALUES (23, '2026_06_15_163631_create_major_competent', 1);
INSERT INTO public.migrations VALUES (24, '2026_06_15_163713_create_major_gallery', 1);


--
-- TOC entry 5290 (class 0 OID 56593)
-- Dependencies: 247
-- Data for Name: missions; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.missions VALUES (1, 'Menyelenggarakan Pendidikan dan Pelatihan untuk menghasilkan lulusan yang memiliki kompetensi standar Internasional.', 1, true, 1, 1, '2026-09-30 17:20:16+07', '2026-09-30 17:20:16+07');
INSERT INTO public.missions VALUES (2, 'Menyelenggarakan Pendidikan dan Pelatihan Pencetak Wirausaha mandiri dan kreatif skala internasional.', 2, true, 1, 1, '2026-09-30 17:20:24+07', '2026-09-30 17:20:24+07');
INSERT INTO public.missions VALUES (3, 'Menyelenggarakan Pendidikan dan Pelatihan untuk menghasilkan lulusan yang memiliki kompetensi standar Internasional.', 3, true, 1, 1, '2026-09-30 17:20:32+07', '2026-09-30 17:20:32+07');
INSERT INTO public.missions VALUES (4, 'Menyelenggarakan pembelajaran dengan pengantar bahasa asing.', 4, true, 1, 1, '2026-09-30 17:20:46+07', '2026-09-30 17:20:46+07');
INSERT INTO public.missions VALUES (5, 'Menyiapkan peserta didik magang di luar negeri.', 5, true, 1, 1, '2026-09-30 17:20:54+07', '2026-09-30 17:20:54+07');
INSERT INTO public.missions VALUES (6, 'Menyelenggarakan Pendidikan dan Pelatihan yang berwawasan lingkungan.', 6, true, 1, 1, '2026-09-30 17:21:00+07', '2026-09-30 17:21:00+07');


--
-- TOC entry 5294 (class 0 OID 56653)
-- Dependencies: 251
-- Data for Name: news_categories; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 5296 (class 0 OID 56680)
-- Dependencies: 253
-- Data for Name: news; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 5264 (class 0 OID 56317)
-- Dependencies: 221
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 5284 (class 0 OID 56520)
-- Dependencies: 241
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 5283 (class 0 OID 56505)
-- Dependencies: 240
-- Data for Name: user_verify_emails; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 5298 (class 0 OID 56716)
-- Dependencies: 255
-- Data for Name: votings; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 5306 (class 0 OID 56828)
-- Dependencies: 263
-- Data for Name: voting_candidates; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 5308 (class 0 OID 56862)
-- Dependencies: 265
-- Data for Name: voting_logs; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 5318 (class 0 OID 0)
-- Dependencies: 242
-- Name: banners_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.banners_id_seq', 1, false);


--
-- TOC entry 5319 (class 0 OID 0)
-- Dependencies: 248
-- Name: events_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.events_id_seq', 1, false);


--
-- TOC entry 5320 (class 0 OID 0)
-- Dependencies: 227
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- TOC entry 5321 (class 0 OID 0)
-- Dependencies: 256
-- Name: feedbacks_categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.feedbacks_categories_id_seq', 1, false);


--
-- TOC entry 5322 (class 0 OID 0)
-- Dependencies: 258
-- Name: feedbacks_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.feedbacks_id_seq', 1, false);


--
-- TOC entry 5323 (class 0 OID 0)
-- Dependencies: 260
-- Name: global_config_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.global_config_id_seq', 1, false);


--
-- TOC entry 5324 (class 0 OID 0)
-- Dependencies: 224
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- TOC entry 5325 (class 0 OID 0)
-- Dependencies: 266
-- Name: major_competent_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.major_competent_id_seq', 1, false);


--
-- TOC entry 5326 (class 0 OID 0)
-- Dependencies: 268
-- Name: major_gallery_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.major_gallery_id_seq', 1, false);


--
-- TOC entry 5327 (class 0 OID 0)
-- Dependencies: 244
-- Name: majors_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.majors_id_seq', 1, false);


--
-- TOC entry 5328 (class 0 OID 0)
-- Dependencies: 237
-- Name: mapping_roles_permissions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mapping_roles_permissions_id_seq', 1, false);


--
-- TOC entry 5329 (class 0 OID 0)
-- Dependencies: 219
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.migrations_id_seq', 24, true);


--
-- TOC entry 5330 (class 0 OID 0)
-- Dependencies: 246
-- Name: missions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.missions_id_seq', 6, true);


--
-- TOC entry 5331 (class 0 OID 0)
-- Dependencies: 250
-- Name: news_categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.news_categories_id_seq', 1, false);


--
-- TOC entry 5332 (class 0 OID 0)
-- Dependencies: 252
-- Name: news_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.news_id_seq', 1, false);


--
-- TOC entry 5333 (class 0 OID 0)
-- Dependencies: 235
-- Name: permissions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.permissions_id_seq', 1, false);


--
-- TOC entry 5334 (class 0 OID 0)
-- Dependencies: 229
-- Name: role_groups_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.role_groups_id_seq', 3, true);


--
-- TOC entry 5335 (class 0 OID 0)
-- Dependencies: 231
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.roles_id_seq', 2, true);


--
-- TOC entry 5336 (class 0 OID 0)
-- Dependencies: 239
-- Name: user_verify_emails_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.user_verify_emails_id_seq', 1, false);


--
-- TOC entry 5337 (class 0 OID 0)
-- Dependencies: 233
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 2, true);


--
-- TOC entry 5338 (class 0 OID 0)
-- Dependencies: 262
-- Name: voting_candidates_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.voting_candidates_id_seq', 1, false);


--
-- TOC entry 5339 (class 0 OID 0)
-- Dependencies: 264
-- Name: voting_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.voting_logs_id_seq', 1, false);


--
-- TOC entry 5340 (class 0 OID 0)
-- Dependencies: 254
-- Name: votings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.votings_id_seq', 1, false);


-- Completed on 2026-10-02 22:05:04

--
-- PostgreSQL database dump complete
--

\unrestrict N2WRNTXSnnV8XHdznpUejDjA7h04ad00I58T6fUiJc7I0J4DiHLUPWfgfGhoe8K

