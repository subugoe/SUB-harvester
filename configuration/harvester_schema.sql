-- phpMyAdmin SQL Dump
-- version 3.3.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 08. Februar 2012 um 11:32
-- Server Version: 5.5.8
-- PHP-Version: 5.3.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `eromm_search`
--
-- CREATE DATABASE `eromm_search` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
-- USE `eromm_search`;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `countries`
--

CREATE TABLE IF NOT EXISTS `countries` (
  `code` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `name_english` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `name_german` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Daten für Tabelle `countries`
--

INSERT INTO `countries` (`code`, `name_english`, `name_german`) VALUES
('AD', 'Andorra', 'Andorra'),
('AE', 'United Arab Emirates', 'Vereinigte Arabische Emirate'),
('AF', 'Afghanistan', 'Afghanistan'),
('AG', 'Antigua and Barbuda', 'Antigua und Barbuda'),
('AI', 'Anguilla', 'Anguilla'),
('AL', 'Albania', 'Albanien'),
('AM', 'Armenia', 'Armenien'),
('AO', 'Angola', 'Angola'),
('AQ', 'Antarctica', 'Antarktika'),
('AR', 'Argentina', 'Argentinien'),
('AS', 'American Samoa', 'Amerikanisch-Samoa'),
('AT', 'Austria', 'Österreich'),
('AU', 'Australia', 'Australien'),
('AW', 'Aruba', 'Aruba'),
('AX', 'Åland Islands', 'Åland'),
('AZ', 'Azerbaijan', 'Aserbaidschan'),
('BA', 'Bosnia and Herzegovina', 'Bosnien und Herzegowina'),
('BB', 'Barbados', 'Barbados'),
('BD', 'Bangladesh', 'Bangladesch'),
('BE', 'Belgium', 'Belgien'),
('BF', 'Burkina Faso', 'Burkina Faso'),
('BG', 'Bulgaria', 'Bulgarien'),
('BH', 'Bahrain', 'Bahrain'),
('BI', 'Burundi', 'Burundi'),
('BJ', 'Benin', 'Benin'),
('BL', 'Saint Barthélemy', 'Saint-Barthélemy'),
('BM', 'Bermuda', 'Bermuda'),
('BN', 'Brunei Darussalam', 'Brunei Darussalam'),
('BO', 'Bolivia, Plurinational State of', 'Bolivien'),
('BQ', 'Bonaire, Saint Eustatius and Saba', 'Bonaire, Sint Eustatius und Saba (Niederlande)'),
('BR', 'Brazil', 'Brasilien'),
('BS', 'Bahamas', 'Bahamas'),
('BT', 'Bhutan', 'Bhutan'),
('BV', 'Bouvet Island', 'Bouvetinsel'),
('BW', 'Botswana', 'Botswana'),
('BY', 'Belarus', 'Belarus (Weißrussland)'),
('BZ', 'Belize', 'Belize'),
('CA', 'Canada', 'Kanada'),
('CC', 'Cocos (Keeling) Islands', 'Kokosinseln'),
('CD', 'Congo, the Democratic Republic of the', 'Kongo, Demokratische Republik (ehem. Zaire)'),
('CF', 'Central African Republic', 'Zentralafrikanische Republik'),
('CG', 'Congo', 'Republik Kongo'),
('CH', 'Switzerland', 'Schweiz (Confoederatio Helvetica)'),
('CI', 'Côte d''Ivoire', 'Côte d''Ivoire (Elfenbeinküste)'),
('CK', 'Cook Islands', 'Cookinseln'),
('CL', 'Chile', 'Chile'),
('CM', 'Cameroon', 'Kamerun'),
('CN', 'China', 'China, Volksrepublik'),
('CO', 'Colombia', 'Kolumbien'),
('CR', 'Costa Rica', 'Costa Rica'),
('CU', 'Cuba', 'Kuba'),
('CV', 'Cape Verde', 'Kap Verde'),
('CW', 'Curaçao', 'Curaçao'),
('CX', 'Christmas Island', 'Weihnachtsinsel'),
('CY', 'Cyprus', 'Zypern'),
('CZ', 'Czech Republic', 'Tschechische Republik'),
('DE', 'Germany', 'Deutschland'),
('DJ', 'Djibouti', 'Dschibuti'),
('DK', 'Denmark', 'Dänemark'),
('DM', 'Dominica', 'Dominica'),
('DO', 'Dominican Republic', 'Dominikanische Republik'),
('DZ', 'Algeria', 'Algerien'),
('EC', 'Ecuador', 'Ecuador'),
('EE', 'Estonia', 'Estland'),
('EG', 'Egypt', 'Ägypten'),
('EH', 'Western Sahara', 'Westsahara'),
('ER', 'Eritrea', 'Eritrea'),
('ES', 'Spain', 'Spanien'),
('ET', 'Ethiopia', 'Äthiopien'),
('FI', 'Finland', 'Finnland'),
('FJ', 'Fiji', 'Fidschi'),
('FK', 'Falkland Islands (Malvinas)', 'Falklandinseln'),
('FM', 'Micronesia, Federated States of', 'Mikronesien'),
('FO', 'Faroe Islands', 'Färöer'),
('FR', 'France', 'Frankreich'),
('GA', 'Gabon', 'Gabun'),
('GB', 'United Kingdom', 'Vereinigtes Königreich Großbritannien und Nordirland'),
('GD', 'Grenada', 'Grenada'),
('GE', 'Georgia', 'Georgien'),
('GF', 'French Guiana', 'Französisch-Guayana'),
('GG', 'Guernsey', 'Guernsey (Kanalinsel)'),
('GH', 'Ghana', 'Ghana'),
('GI', 'Gibraltar', 'Gibraltar'),
('GL', 'Greenland', 'Grönland'),
('GM', 'Gambia', 'Gambia'),
('GN', 'Guinea', 'Guinea'),
('GP', 'Guadeloupe', 'Guadeloupe'),
('GQ', 'Equatorial Guinea', 'Äquatorialguinea'),
('GR', 'Greece', 'Griechenland'),
('GS', 'South Georgia and the South Sandwich Islands', 'Südgeorgien und die Südlichen Sandwichinseln'),
('GT', 'Guatemala', 'Guatemala'),
('GU', 'Guam', 'Guam'),
('GW', 'Guinea-Bissau', 'Guinea-Bissau'),
('GY', 'Guyana', 'Guyana'),
('HK', 'Hong Kong', 'Hongkong'),
('HM', 'Heard Island and McDonald Islands', 'Heard und McDonaldinseln'),
('HN', 'Honduras', 'Honduras'),
('HR', 'Croatia', 'Kroatien'),
('HT', 'Haiti', 'Haiti'),
('HU', 'Hungary', 'Ungarn'),
('ID', 'Indonesia', 'Indonesien'),
('IE', 'Ireland', 'Irland'),
('IL', 'Israel', 'Israel'),
('IM', 'Isle of Man', 'Insel Man'),
('IN', 'India', 'Indien'),
('IO', 'British Indian Ocean Territory', 'Britisches Territorium im Indischen Ozean'),
('IQ', 'Iraq', 'Irak'),
('IR', 'Iran, Islamic Republic of', 'Iran, Islamische Republik'),
('IS', 'Iceland', 'Island'),
('IT', 'Italy', 'Italien'),
('JE', 'Jersey', 'Jersey (Kanalinsel)'),
('JM', 'Jamaica', 'Jamaika'),
('JO', 'Jordan', 'Jordanien'),
('JP', 'Japan', 'Japan'),
('KE', 'Kenya', 'Kenia'),
('KG', 'Kyrgyzstan', 'Kirgisistan'),
('KH', 'Cambodia', 'Kambodscha'),
('KI', 'Kiribati', 'Kiribati'),
('KM', 'Comoros', 'Komoren'),
('KN', 'Saint Kitts and Nevis', 'St. Kitts und Nevis'),
('KP', 'Korea, Democratic People''s Republic of', 'Korea, Demokratische Volksrepublik (Nordkorea)'),
('KR', 'Korea, Republic of', 'Korea, Republik (Südkorea)'),
('KW', 'Kuwait', 'Kuwait'),
('KY', 'Cayman Islands', 'Kaimaninseln'),
('KZ', 'Kazakhstan', 'Kasachstan'),
('LA', 'Lao People''s Democratic Republic', 'Laos, Demokratische Volksrepublik'),
('LB', 'Lebanon', 'Libanon'),
('LC', 'Saint Lucia', 'St. Lucia'),
('LI', 'Liechtenstein', 'Liechtenstein'),
('LK', 'Sri Lanka', 'Sri Lanka'),
('LR', 'Liberia', 'Liberia'),
('LS', 'Lesotho', 'Lesotho'),
('LT', 'Lithuania', 'Litauen'),
('LU', 'Luxembourg', 'Luxemburg'),
('LV', 'Latvia', 'Lettland'),
('LY', 'Libyan Arab Jamahiriya', 'Libysch-Arabische Dschamahirija (Libyen)'),
('MA', 'Morocco', 'Marokko'),
('MC', 'Monaco', 'Monaco'),
('MD', 'Moldova, Republic of', 'Moldawien (Republik Moldau)'),
('ME', 'Montenegro', 'Montenegro'),
('MF', 'Saint Martin (French part)', 'Saint-Martin (franz. Teil)'),
('MG', 'Madagascar', 'Madagaskar'),
('MH', 'Marshall Islands', 'Marshallinseln'),
('MK', 'Macedonia, the former Yugoslav Republic of', 'Mazedonien, ehem. jugoslawische Republik [2b]'),
('ML', 'Mali', 'Mali'),
('MM', 'Myanmar', 'Myanmar (Burma)'),
('MN', 'Mongolia', 'Mongolei'),
('MO', 'Macao', 'Macao'),
('MP', 'Northern Mariana Islands', 'Nördliche Marianen'),
('MQ', 'Martinique', 'Martinique'),
('MR', 'Mauritania', 'Mauretanien'),
('MS', 'Montserrat', 'Montserrat'),
('MT', 'Malta', 'Malta'),
('MU', 'Mauritius', 'Mauritius'),
('MV', 'Maldives', 'Malediven'),
('MW', 'Malawi', 'Malawi'),
('MX', 'Mexico', 'Mexiko'),
('MY', 'Malaysia', 'Malaysia'),
('MZ', 'Mozambique', 'Mosambik'),
('NA', 'Namibia', 'Namibia'),
('NC', 'New Caledonia', 'Neukaledonien'),
('NE', 'Niger', 'Niger'),
('NF', 'Norfolk Island', 'Norfolkinsel'),
('NG', 'Nigeria', 'Nigeria'),
('NI', 'Nicaragua', 'Nicaragua'),
('NL', 'Netherlands', 'Niederlande'),
('NO', 'Norway', 'Norwegen'),
('NP', 'Nepal', 'Nepal'),
('NR', 'Nauru', 'Nauru'),
('NU', 'Niue', 'Niue'),
('NZ', 'New Zealand', 'Neuseeland'),
('OM', 'Oman', 'Oman'),
('PA', 'Panama', 'Panama'),
('PE', 'Peru', 'Peru'),
('PF', 'French Polynesia', 'Französisch-Polynesien'),
('PG', 'Papua New Guinea', 'Papua-Neuguinea'),
('PH', 'Philippines', 'Philippinen'),
('PK', 'Pakistan', 'Pakistan'),
('PL', 'Poland', 'Polen'),
('PM', 'Saint Pierre and Miquelon', 'Saint-Pierre und Miquelon'),
('PN', 'Pitcairn', 'Pitcairninseln'),
('PR', 'Puerto Rico', 'Puerto Rico'),
('PS', 'Palestinian Territory, Occupied', 'Palästinensische Autonomiegebiete'),
('PT', 'Portugal', 'Portugal'),
('PW', 'Palau', 'Palau'),
('PY', 'Paraguay', 'Paraguay'),
('QA', 'Qatar', 'Katar'),
('RE', 'Réunion', 'Réunion'),
('RO', 'Romania', 'Rumänien'),
('RS', 'Serbia', 'Serbien'),
('RU', 'Russian Federation', 'Russische Föderation'),
('RW', 'Rwanda', 'Ruanda'),
('SA', 'Saudi Arabia', 'Saudi-Arabien'),
('SB', 'Solomon Islands', 'Salomonen'),
('SC', 'Seychelles', 'Seychellen'),
('SD', 'Sudan', 'Sudan'),
('SE', 'Sweden', 'Schweden'),
('SG', 'Singapore', 'Singapur'),
('SH', 'Saint Helena, Ascension and Tristan da Cunha', 'St. Helena'),
('SI', 'Slovenia', 'Slowenien'),
('SJ', 'Svalbard and Jan Mayen', 'Svalbard und Jan Mayen'),
('SK', 'Slovakia', 'Slowakei'),
('SL', 'Sierra Leone', 'Sierra Leone'),
('SM', 'San Marino', 'San Marino'),
('SN', 'Senegal', 'Senegal'),
('SO', 'Somalia', 'Somalia'),
('SR', 'Suriname', 'Suriname'),
('ST', 'Sao Tome and Principe', 'São Tomé und Príncipe'),
('SV', 'El Salvador', 'El Salvador'),
('SX', 'Sint Maarten (Dutch part)', 'Sint Maarten (niederl. Teil)'),
('SY', 'Syrian Arab Republic', 'Syrien, Arabische Republik'),
('SZ', 'Swaziland', 'Swasiland'),
('TC', 'Turks and Caicos Islands', 'Turks- und Caicosinseln'),
('TD', 'Chad', 'Tschad'),
('TF', 'French Southern Territories', 'Französische Süd- und Antarktisgebiete'),
('TG', 'Togo', 'Togo'),
('TH', 'Thailand', 'Thailand'),
('TJ', 'Tajikistan', 'Tadschikistan'),
('TK', 'Tokelau', 'Tokelau'),
('TL', 'Timor-Leste', 'Osttimor (Timor-Leste)'),
('TM', 'Turkmenistan', 'Turkmenistan'),
('TN', 'Tunisia', 'Tunesien'),
('TO', 'Tonga', 'Tonga'),
('TR', 'Turkey', 'Türkei'),
('TT', 'Trinidad and Tobago', 'Trinidad und Tobago'),
('TV', 'Tuvalu', 'Tuvalu'),
('TW', 'Taiwan, Province of China', 'Republik China (Taiwan)'),
('TZ', 'Tanzania, United Republic of', 'Tansania, Vereinigte Republik'),
('UA', 'Ukraine', 'Ukraine'),
('UG', 'Uganda', 'Uganda'),
('UM', 'United States Minor Outlying Islands', 'United States Minor Outlying Islands'),
('US', 'United States', 'Vereinigte Staaten von Amerika'),
('UY', 'Uruguay', 'Uruguay'),
('UZ', 'Uzbekistan', 'Usbekistan'),
('VA', 'Holy See (Vatican City State)', 'Vatikanstadt'),
('VC', 'Saint Vincent and the Grenadines', 'St. Vincent und die Grenadinen'),
('VE', 'Venezuela, Bolivarian Republic of', 'Venezuela'),
('VG', 'Virgin Islands, British', 'Britische Jungferninseln'),
('VI', 'Virgin Islands, U.S.', 'Amerikanische Jungferninseln'),
('VN', 'Viet Nam', 'Vietnam'),
('VU', 'Vanuatu', 'Vanuatu'),
('WF', 'Wallis and Futuna', 'Wallis und Futuna'),
('WS', 'Samoa', 'Samoa'),
('YE', 'Yemen', 'Jemen'),
('YT', 'Mayotte', 'Mayotte'),
('ZA', 'South Africa', 'Südafrika'),
('ZM', 'Zambia', 'Sambia'),
('ZW', 'Zimbabwe', 'Simbabwe');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `oai_logs`
--

CREATE TABLE IF NOT EXISTS `oai_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` datetime NOT NULL,
  `oai_set` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `type` tinyint(4) NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `added` int(11) NOT NULL,
  `deleted` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `oai_set` (`oai_set`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `oai_logs`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `oai_sets`
--

CREATE TABLE IF NOT EXISTS `oai_sets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `oai_source` int(11) NOT NULL,
  `setSpec` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `setName` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `online` tinyint(1) NOT NULL,
  `harvest` tinyint(1) NOT NULL,
  `harvest_status` tinyint(4) NOT NULL,
  `index_status` tinyint(4) NOT NULL,
  `last_harvested` datetime DEFAULT NULL,
  `last_indexed` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oai_source` (`oai_source`),
  KEY `harvest_status` (`harvest_status`),
  KEY `index_status` (`index_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `oai_sets`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `oai_source_edit_sessions`
--

CREATE TABLE IF NOT EXISTS `oai_source_edit_sessions` (
  `oai_source` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY (`oai_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Daten für Tabelle `oai_source_edit_sessions`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `oai_sources`
--

CREATE TABLE IF NOT EXISTS `oai_sources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `view_creator` tinyint(1) NOT NULL,
  `view_contributor` tinyint(1) NOT NULL,
  `view_publisher` tinyint(1) NOT NULL,
  `view_date` tinyint(1) NOT NULL,
  `view_identifier` tinyint(1) NOT NULL,
  `index_relation` tinyint(1) NOT NULL,
  `index_creator` tinyint(1) NOT NULL,
  `index_contributor` tinyint(1) NOT NULL,
  `index_publisher` tinyint(1) NOT NULL,
  `index_date` tinyint(1) NOT NULL,
  `index_identifier` tinyint(1) NOT NULL,
  `index_subject` tinyint(1) NOT NULL,
  `index_description` tinyint(1) NOT NULL,
  `index_source` tinyint(1) NOT NULL,
  `dc_date_postproc` tinyint(4) NOT NULL,
  `identifier_filter` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `identifier_resolver` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `identifier_resolver_filter` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `identifier_alternative` varchar(150) COLLATE utf8_unicode_ci NOT NULL,
  `country_code` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL,
  `added` datetime NOT NULL,
  `harvest_period` smallint(6) NOT NULL,
  `from` date DEFAULT NULL,
  `harvested_since` date DEFAULT NULL,
  `last_harvest` date DEFAULT NULL,
  `reindex` tinyint(1) NOT NULL,
  `comment` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `active` (`active`),
  KEY `next_harvest` (`last_harvest`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `oai_sources`
--

