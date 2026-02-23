<?php header("Content-type: text/css"); ?>

@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap');

/* Notes:
Theme inspired by "4n Career Day" poster colors
Light theme implementation
All existing class names and IDs preserved for functionality
*/

@import url("/style/font.css");

@import url("/style/color.css?cachebuster=<?= date("YmdH") ?>");

::selection {
background-color: var(--accent-primary);
color: var(--color-white);
}

/* --- Reset & Base --- */

*, *::before, *::after {
box-sizing: border-box;
font-family: var(--font-body);
}

body, header, footer, main, nav, hr, p {
margin: 0;
}

/* --- Layout --- */

html {
margin: 0;
padding: 0;
overflow-x: hidden;
word-break: break-word;
background: var(--bg-primary); /* Base color */
min-height: 100vh;
}

body {
margin: 0;
padding: 1.5rem;
display: flex;
flex-direction: column;
align-items: center;
min-height: 100dvh;
width: 100%;
color: var(--text-primary);
line-height: 1.6;
}

/* Background Pattern Overlay - Matches Static Site .site-bg */
body.bg-pattern::after {
content: "";
position: fixed;
top: 0;
left: 0;
width: 100%;
height: 100%;
z-index: -1;
background-image: url('/resources/images/background.svg');
background-size: cover;
background-position: center;
opacity: 0.04; /* Opaque as requested */
pointer-events: none;
}

hr {
margin: 1rem 0;
border: none;
height: 1px;
background-color: var(--border);
}

a,
a:visited {
text-decoration: none;
color: var(--accent-secondary);
font-weight: 600;
transition: color var(--transition-fast);
}

a:hover {
color: var(--color-accent-light);
text-decoration: none;
}

button {
padding: 0.75rem 1.5rem;
border: none;
border-radius: var(--radius-md);
background: linear-gradient(135deg, var(--brand-maroon) 0%, var(--brand-orange) 100%);
color: #ffffff;
font-weight: 600;
cursor: pointer;
transition: all var(--transition-normal);
box-shadow: var(--shadow-sm);
}

.btn-gradient, .btn-primary,
button:not(.btn-secondary):not(.btn-secondary-sm):not(.btn-outline):not(.btn-outline-sm):not(.tab-btn):not(.btn-action):not(.btn-icon)
{
color: #ffffff !important;
}

.btn-secondary, .btn-secondary-sm, .btn-outline, .tab-btn:not(.active) {
background: var(--bg-secondary);
color: var(--text-primary) !important;
border: 1px solid var(--border);
}

button:hover {
transform: translateY(-2px);
box-shadow: 0 10px 20px rgba(161, 32, 36, 0.2);
}

header, footer, main, nav, hr {
width: 100%;
}

header, footer {
display: flex;
flex-direction: column;
align-items: center;
}

/* --- Modern Header --- */

.header_title_container {
position: relative;
width: 100%;
text-align: center;
padding: 1rem;

& > .polygon {
display: none; /* Clean look without animated polygons */
}

& > .text {
position: relative;
text-align: center;
padding: 2rem 1.5rem;
margin: 0;
font-family: var(--font-heading);
font-size: 2.5rem;
font-weight: 700;
background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
-webkit-background-clip: text;
-webkit-text-fill-color: transparent;
background-clip: text;

&::selection {
background-color: var(--accent-primary);
-webkit-text-fill-color: var(--color-white);
}
}
}

@media (max-width: 640px) {
body {
padding: 0.75rem;
}
.header_title_container > .text {
font-size: 1.75rem;
padding: 1rem 1rem;
}
}

/* --- Modern Navigation --- */

nav {
display: flex;
justify-content: center;
flex-wrap: wrap;
gap: 0.75rem;
padding: 0.75rem 0;
}

nav a {
padding: 0.75rem 1.5rem;
border-radius: var(--radius-lg);
background-color: var(--bg-card);
color: var(--text-secondary);
font-weight: 500;
border: 1px solid var(--border);
transition: all var(--transition-normal);
}

nav a:hover {
background: linear-gradient(135deg, var(--brand-maroon), var(--brand-orange));
color: #ffffff !important;
border-color: transparent;
transform: translateY(-2px);
box-shadow: 0 10px 20px rgba(161, 32, 36, 0.2);
}

/* --- Main Content --- */

main {
display: flex;
flex-direction: column;
align-items: center;
justify-content: center;
gap: 1.5rem;
padding: 2rem 0;
}

/* --- Modern Forms --- */

form {
display: flex;
flex-direction: column;
gap: 1rem;
width: 100%;
}

form fieldset {
display: flex;
flex-direction: column;
gap: 1rem;
padding: 1.5rem;
border: 1px solid var(--border);
border-radius: var(--radius-xl);
background-color: var(--bg-card);
}

form fieldset legend {
padding: 0 0.75rem;
font-weight: 600;
color: var(--text-secondary);
}

form input,
form button,
form select,
form textarea,
form input::file-selector-button {
padding: 0.875rem 1rem;
border: 1px solid var(--border);
border-radius: var(--radius-md);
font-size: 1rem;
background-color: var(--bg-secondary);
color: var(--text-primary);
transition: all var(--transition-fast);
}

form input:focus,
form select:focus,
form textarea:focus {
outline: none;
border-color: var(--brand-maroon);
box-shadow: 0 0 0 3px rgba(161, 32, 36, 0.15);
}

form button {
border: none;
background: linear-gradient(135deg, var(--brand-maroon), var(--brand-orange));
color: #ffffff;
font-weight: 600;
cursor: pointer;
}

form button:hover {
transform: translateY(-2px);
box-shadow: 0 10px 20px rgba(161, 32, 36, 0.2);
}

form input[type="file"] {
width: 100%;
padding: 1.5rem;
border: 2px dashed var(--brand-maroon);
border-radius: var(--radius-lg);
background: rgba(161, 32, 36, 0.05);
cursor: pointer;
transition: all var(--transition-fast);
}

form input[type="file"]:hover {
background: rgba(161, 32, 36, 0.1);
border-color: var(--brand-orange);
}

form input[type="file"]::file-selector-button {
padding: 0.625rem 1.25rem;
margin-right: 1rem;
border: none;
border-radius: var(--radius-md);
background: linear-gradient(135deg, var(--brand-maroon), var(--brand-orange));
color: #ffffff;
font-weight: 600;
cursor: pointer;
transition: all var(--transition-fast);
}

form input[type="file"]::file-selector-button:hover {
transform: scale(1.02);
}

form select {
width: 100%;
background-color: var(--bg-secondary);
}

.form-jobpositions {
.info {
text-align: center;
margin: 0;
color: var(--text-secondary);
}

& input[type="text"],
& textarea {
padding: 0.75rem;
}
}

.horizontal_buttons {
display: flex;
flex-direction: row;
justify-content: flex-end;
gap: 0.75rem;
}

/* --- Modern Dialogs --- */

dialog[open] {
position: fixed;
top: 2rem;
left: 50%;
transform: translateX(-50%);

display: flex;
flex-direction: column;
gap: 1rem;

max-width: 90vw;
max-height: calc(100vh - 4rem);
width: 700px;

overflow-y: auto;

border: 1px solid var(--border);
border-radius: var(--radius-xl);
background-color: var(--bg-card);
box-shadow: var(--shadow-lg);
padding: 1.5rem;
color: var(--text-primary);
}

dialog::backdrop {
background-color: rgba(0, 0, 0, 0.7);
backdrop-filter: blur(8px);
-webkit-backdrop-filter: blur(8px);
cursor: pointer;
position: fixed;
inset: 0;
width: 100%;
height: 100%;
margin: 0;
padding: 0;
border: none;
}

dialog p {
text-align: center;
color: var(--text-secondary);
}

dialog button {
padding: 0.75rem 1.25rem;
}

/* X Close button styling */
dialog .dialog-close {
position: absolute;
top: 0.75rem;
right: 0.75rem;
width: 2rem;
height: 2rem;
padding: 0;
border: none;
border-radius: 50%;
background: rgba(255, 255, 255, 0.1);
color: var(--text-secondary);
font-size: 1.25rem;
line-height: 1;
cursor: pointer;
transition: all var(--transition-fast);
display: flex;
align-items: center;
justify-content: center;
}

dialog .dialog-close:hover {
background: rgba(255, 255, 255, 0.2);
color: var(--text-primary);
transform: scale(1.1);
}

#iwer_info_dialog[open] {
width: auto;
max-width: 95vw;

& > form > label {
display: flex;
flex-direction: column;
gap: 0.5rem;
font-weight: 500;
color: var(--text-secondary);
}
}

#dialog_action[open] {
& hr {
margin: 0.5rem 0;
}
}

/* --- Typography --- */

h1, h2, h3, h4, h5, h6 {
font-family: var(--font-heading);
line-height: 1.3;
margin: 0;
color: var(--text-primary);
}

h1 { font-size: 2rem; }
h2 { font-size: 1.5rem; }
h3 { font-size: 1.25rem; }
h6 { font-size: 0.875rem; color: var(--text-secondary); }

/* --- Utilities --- */

.spacer {
margin: auto;
}

/* --- Interviewer Checkboxes --- */

#iwer_checkboxes {
display: flex;
flex-direction: column;
align-items: stretch;
border: 1px solid var(--border);
border-radius: var(--radius-xl);
overflow: hidden;
background-color: var(--bg-card);

& > label {
display: flex;
flex-direction: row;
align-items: center;
flex-grow: 1;
gap: 1rem;
padding: 1.25rem;
transition: all var(--transition-fast);
border-bottom: 1px solid var(--border);

&:last-child {
border-bottom: none;
}

&:hover {
background-color: var(--bg-secondary);
}

&:has(> input[style="display: none;"]) {
padding: 1.25rem;
}

& > img {
aspect-ratio: 1 / 1;
object-fit: cover;
width: clamp(48px, 15dvw, 72px);
border-radius: var(--radius-md);
border: 2px solid var(--border);
}
}
}

/* --- Modern Card Styling with Glow Effects --- */

.container_interviewers, .dialog_details[open] {
display: flex;
flex-direction: row;
flex-wrap: wrap;
justify-content: center;
align-items: stretch;
width: 100%;
gap: 1.5rem;

& > .interviewer {
display: flex;
flex-direction: column;
width: 100%;
background-color: var(--bg-card);
border: 1px solid var(--border);
border-radius: var(--radius-xl);
overflow: hidden;
transition: all var(--transition-normal);
position: relative;

& > .info {
display: flex;
flex-direction: row;
align-items: center;
gap: 1rem;
padding: 1.5rem;

& > .image {
aspect-ratio: 1 / 1;
object-fit: cover;
width: clamp(56px, 15dvw, 80px);
border-radius: var(--radius-md);
border: 2px solid var(--border);
}

& > .text {
line-height: 1.5;
font-weight: 600;
font-size: 1.1rem;
}
}

& > .status_indicator {
width: 100%;
height: 8px;
background-color: var(--text-secondary);
position: absolute;
top: 0;
left: 0;
}

& > .status_indicator--available {
background-color: var(--color-status--available);
}

& > .status_indicator--calling {
background-color: var(--color-status--calling);
animation: pulse-glow 2s ease-in-out infinite;
}

& > .status_indicator--decision {
background-color: var(--color-status--decision);
animation: pulse-glow-amber 2s ease-in-out infinite;
}

& > .status_indicator--happening {
background-color: var(--color-status--happening);
animation: pulse-glow-red 2s ease-in-out infinite;
}

& > .status_indicator--paused {
background-color: var(--color-status--unavailable);
}

& > .status_information {
flex-grow: 1;
text-align: center;
line-height: 1.4;
padding: 0.75rem 1rem;
background-color: var(--bg-secondary);
font-size: 0.9rem;
color: var(--text-secondary);
height: 140px;
flex-shrink: 0;
overflow: hidden;
display: flex;
flex-direction: column;
justify-content: center;
align-items: center;
flex-grow: 0;

& > span,
& > .called-number {
font-family: 'Consolas', monospace;
font-weight: 800;
color: var(--brand-maroon);
font-size: 1.3rem;
background: rgba(157, 28, 32, 0.05);
padding: 2px 8px;
border-radius: var(--radius-sm);
display: inline-block;
margin-top: 4px;
}
}
}

/* Glow intensity increase */
@keyframes pulse-glow {
0%, 100% { opacity: 1; filter: brightness(1.2); }
50% { opacity: 0.7; filter: brightness(1); }
}

& > .interviewer:hover {
cursor: pointer;
transform: translateY(-4px);
box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
border-color: var(--brand-maroon);
}

/* Glow effects for different states */
& > .interviewer:has(.status_indicator--calling) {
box-shadow: var(--glow-calling);
}

& > .interviewer:has(.status_indicator--decision) {
box-shadow: var(--glow-decision);
}

& > .interviewer:has(.status_indicator--happening) {
box-shadow: var(--glow-happening);
}
}

.called-number {
font-family: 'Consolas', monospace;
font-weight: 800;
color: var(--brand-maroon);
font-size: 1.3rem;
background: rgba(157, 28, 32, 0.05);
padding: 2px 8px;
border-radius: var(--radius-sm);
display: inline-block;
}

@keyframes pulse-glow {
0%, 100% { opacity: 1; }
50% { opacity: 0.6; }
}

@keyframes pulse-glow-amber {
0%, 100% { opacity: 1; box-shadow: 0 0 10px var(--color-status--decision); }
50% { opacity: 0.7; box-shadow: 0 0 20px var(--color-status--decision); }
}

@keyframes pulse-glow-red {
0%, 100% { opacity: 1; }
50% { opacity: 0.7; }
}

/* --- Dialog Details --- */

.dialog_details[open] {
flex-direction: column;
align-items: stretch;
justify-content: flex-start;
flex-wrap: nowrap;
width: 700px;
max-width: 95vw;
outline: none;
background-color: var(--bg-card);

& p {
text-align: left;
}

& > .interviewer:hover {
cursor: default;
transform: none;
}

& > .quueueue {
display: flex;
flex-direction: column;

& > .title_with_count {
display: flex;
flex-direction: row;
font-weight: 600;

& > .count {
margin-left: auto;
color: var(--accent-primary);
}
}

& > .horizontal_scrollable {
display: flex;
flex-direction: row;
flex-wrap: nowrap;
overflow-y: hidden;
overflow-x: auto;
gap: 1.5rem;
padding: 0.75rem 0;

& > .interviewee {
text-wrap: nowrap;
font-size: 1.75rem;
font-weight: 700;
}

& > .interviewee--unavailable {
color: var(--color-status--unavailable);
}

& > .interviewee--available {
color: var(--color-status--available);
}

& > .interviewee--calling {
color: var(--color-status--calling);
}

& > .interviewee--decision {
color: var(--color-status--decision);
}

& > .interviewee--happening {
color: var(--color-status--happening);
}

& > .interviewee--completed {
color: var(--color-status--completed);
}

&.interviewee--self {
text-decoration: underline;
text-underline-offset: 4px;
text-decoration-thickness: 3px;
}
}
}
}

/* --- Suggestions Main --- */

#suggestions-main {
& p,
& h1,
& h2,
& h3 {
text-align: center;
}

& button {
padding: 0.875rem 1.75rem;
}

& #current_url_qr {
aspect-ratio: 1 / 1;
object-fit: cover;
width: clamp(0px, 75vw, 256px);
border-radius: var(--radius-xl);
border: 2px solid var(--border);
background: var(--bg-card);
padding: 1rem;
}

& > .interviewer {
display: flex;
flex-direction: column;
align-items: center;
gap: 0.5rem;
}
}

/* --- Index Main --- */

#index-main {
text-align: center;
width: 100%;
max-width: 1000px;
margin: 0 auto;

& p {
color: var(--text-secondary);
}
}

.home-grid {
display: grid;
grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
gap: 1.5rem;
width: 100%;
margin-top: 1rem;
}

.home-card {
padding: 2rem;
border-radius: var(--radius-xl);
text-align: left;
display: flex;
flex-direction: column;
gap: 1.25rem;
transition: transform var(--transition-normal), box-shadow var(--transition-normal);
height: 100%;
background-color: var(--bg-card);
}

.home-card:hover {
transform: translateY(-5px);
box-shadow: var(--shadow-lg);
border-color: var(--brand-maroon);
}

.home-card__icon {
font-size: 2.5rem;
}

.home-card__content h3 {
margin-bottom: 0.75rem;
color: var(--brand-maroon);
font-size: 1.4rem;
}

.home-card__content p {
margin: 0;
line-height: 1.6;
}

.home-card__highlight {
margin-top: 1rem;
padding: 1rem;
background: rgba(157, 28, 32, 0.05);
border-left: 4px solid var(--brand-maroon);
border-radius: var(--radius-sm);
font-size: 0.95rem;
color: var(--text-primary);
}

.home-card--share {
grid-column: 1 / -1;
}

.share-actions {
display: flex;
align-items: center;
gap: 2rem;
margin-top: 1rem;
}

.share-qr {
width: 140px;
height: 140px;
padding: 10px;
background: white;
border-radius: var(--radius-lg);
border: 1px solid var(--border);
}

.share-text {
flex: 1;
display: flex;
flex-direction: column;
gap: 1rem;
}

.share-text p {
margin: 0;
font-size: 0.95rem;
}

@media (max-width: 600px) {
.share-actions {
flex-direction: column;
align-items: flex-start;
gap: 1.25rem;
}

.home-grid {
grid-template-columns: 1fr;
}
}

/* --- Info Dialog with Status Badges --- */

.info-dialog {
& p {
text-align: left;
}

& ul {
display: flex;
flex-direction: column;
gap: 0.75rem;
margin: 0;
padding-left: 1.25rem;
}

& .av, & .ca, & .de, & .ha, & .pa {
display: inline-block;
padding: 0.25rem 0.75rem;
border-radius: var(--radius-md);
font-weight: 600;
font-size: 0.85rem;
text-transform: uppercase;
letter-spacing: 0.05em;
}

& .av {
color: var(--color-white);
background-color: var(--color-status--available);
}

& .ca {
color: var(--color-white);
background-color: var(--color-status--calling);
}

& .de {
color: var(--bg-primary);
background-color: var(--color-status--decision);
}

& .ha {
color: var(--color-white);
background-color: var(--color-status--happening);
}

& .pa {
color: var(--color-white);
background-color: var(--color-status--unavailable);
}
}

/* --- Footer --- */

footer {
padding: 1.5rem 0;

& p {
color: var(--text-secondary);
font-size: 0.875rem;
}

& a {
color: var(--accent-secondary);
}
}

/* --- Responsive --- */

@media screen and (min-width: 501px) {
main {
max-width: 900px;
}

dialog {
max-width: 95vw;
width: 700px;
}
}

@media screen and (min-width: 1024px) {
main {
max-width: 1200px;
}
}

@media screen and (min-width: calc(320px * 2 + 3rem)) {
main:has(.container_interviewers) {
max-width: 100%;

& > .container_interviewers > .interviewer {
max-width: 320px;
}
}
}

@media (max-width: 800px) { /* Increased breakpoint for hamburger */
dialog[open], .dialog_details[open] {
width: 95vw;
max-width: 95vw;
padding: 1rem;
}
}


/* --- New Header Styles (Ported) --- */

:root {
--bg-page: #f8fafc;
/* --bg-card: rgba(255, 255, 255, 0.8); */ /* Using existing --bg-card */
--accent-red: #9E1B32;
--accent-orange: #F7911E;
--accent-teal: #005C84;
--accent-green: #00AD6E;
/* --text-primary: #0f172a; */ /* Using existing */
/* --text-secondary: #475569; */ /* Using existing */
--border-glass: rgba(0, 0, 0, 0.1);
--font-heading: 'Outfit', sans-serif; /* Override for header components */
--transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
--shadow-soft: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
}

/* Adjust main content padding because header is fixed */
body {
padding-top: 120px !important; /* Override existing padding */
}

.glass-nav {
position: fixed;
top: 1.25rem;
left: 50%;
transform: translateX(-50%);
width: calc(100% - 2.5rem);
max-width: 1300px;
background: rgba(255, 255, 255, 0.85); /* Matches static */
backdrop-filter: blur(12px);
border: 1px solid var(--border-glass);
border-radius: 3rem;
z-index: 1000;
padding: 0.75rem 1.5rem;
box-shadow: var(--shadow-soft);
font-family: 'Outfit', sans-serif !important; /* Force font */

/* RESET GENERIC NAV STYLES */
display: block !important; /* Override flex */
justify-content: unset !important;
gap: unset !important;
}

.glass-nav * {
font-family: 'Outfit', sans-serif !important;
box-sizing: border-box;
}

/* Reset generic nav link styles for ALL links inside glass-nav */
.glass-nav a {
padding: 0 !important;
border-radius: 0 !important;
background-color: transparent !important;
color: inherit;
font-weight: normal;
border: none !important;
transition: none;
display: inline-flex; /* Reset display */
}

.glass-nav a:hover {
background: transparent !important;
color: inherit !important;
}

.nav-content {
display: flex;
flex-direction: row;
gap: 1.5rem;
justify-content: center; /* Changed to center to support inner width */
align-items: center;
}

.nav-main-row {
display: flex;
justify-content: space-between;
align-items: center;
width: 100%;
position: relative; /* Anchor for absolute mobile menu */
}

.header-left {
display: flex;
align-items: center;
gap: 1.5rem;
}

/* ── Global Candidate Status Colors ── */
.interviewee--unavailable { color: var(--color-status--unavailable) !important; }
.interviewee--available { color: var(--color-status--available) !important; }
.interviewee--calling { color: var(--color-status--calling) !important; }
.interviewee--decision { color: var(--color-status--decision) !important; }
.interviewee--happening { color: var(--color-status--happening) !important; }
.interviewee--completed { color: var(--color-status--completed) !important; }
.interviewee--self { text-decoration: underline !important; text-underline-offset: 4px; text-decoration-thickness: 3px;
}

.header-logos {
display: flex;
align-items: center;
gap: 1.5rem;
}

.header-logos h2 {
font-family: 'Outfit', sans-serif;
font-size: 1.2rem;
font-weight: 700;
margin: 0;
margin-right: 0.5rem;
white-space: nowrap;
letter-spacing: -0.5px;
color: var(--text-primary);
cursor: default;
}

.header-logos a {
display: flex;
align-items: center;
}

.logo-link {
text-decoration: none;
color: inherit;
transition: none; /* Disable transition */
}

.logo-link:hover {
opacity: 1 !important; /* Force no opacity change */
color: inherit !important; /* Force no color change */
}

.nav-logo {
height: 35px;
width: auto;
transition: none; /* Disable transition */
}

.nav-logo:hover {
transform: none !important; /* Force no scale */
filter: none !important;
}

.nav-links {
display: flex;
gap: 2rem;
align-items: center;
}

/* Specific styles for nav links to restore desired look */
.nav-links a {
color: var(--text-secondary) !important; /* Re-apply color */
text-decoration: none;
font-weight: 500 !important;
transition: var(--transition) !important;
padding: 0.5rem 1rem !important; /* Re-apply padding */
border-radius: 20px !important;
background-color: transparent !important; /* Explicit override */
border: none !important; /* Explicit override */
display: inline-block !important;
white-space: nowrap !important; /* Prevent vertical wrapping */
}

.nav-links a:hover,
.nav-links a.active {
background: rgba(0, 0, 0, 0.05) !important;
box-shadow: none;
transform: none;
border-color: transparent;
color: var(--text-secondary) !important; /* Keep original color */
}

.btn-hub {
border: 1.5px solid var(--accent-teal) !important;
border-radius: 25px !important;
padding: 0.4rem 1.2rem !important;
color: var(--accent-teal) !important;
font-weight: 600 !important;
transition: var(--transition);
background: transparent !important;
}

.btn-hub:hover {
background: var(--accent-teal) !important;
color: white !important;
}

.nav-links a.cta-nav {
background: linear-gradient(135deg, var(--brand-maroon) 0%, var(--brand-orange) 100%) !important;
padding: 0.6rem 1.5rem !important;
border-radius: 25px !important;
color: white !important;
box-shadow: 0 4px 15px rgba(157, 28, 32, 0.3);
font-weight: 600 !important;
border: none !important;
transition: var(--transition) !important;
display: inline-block !important;
}

.nav-links a.cta-nav:hover {
transform: translateY(-2px) !important;
box-shadow: 0 8px 25px rgba(157, 28, 32, 0.4) !important;
color: white !important;
}

.gradient-text {
background: linear-gradient(to right, var(--accent-red), var(--accent-orange));
-webkit-background-clip: text;
background-clip: text;
-webkit-text-fill-color: transparent;
}

.animate-fade-in {
animation: fadeIn 0.8s ease-out forwards;
}

@keyframes fadeIn {
from { opacity: 0; transform: translateY(10px); }
to { opacity: 1; transform: translateY(0); }
}

/* --- Hamburger Button --- */
.hamburger {
display: none;
flex-direction: column;
justify-content: center;
gap: 6px;
background: none;
border: none;
cursor: pointer;
padding: 4px;
z-index: 1001;
width: 40px;
height: 40px;
box-shadow: none !important; /* Override default button shadow */
}

.hamburger span {
display: block;
width: 28px;
height: 3px;
background: var(--text-primary);
border-radius: 2px;
transition: var(--transition);
}

.hamburger.active span:nth-child(1) {
transform: rotate(45deg) translate(7px, 7px);
}

.hamburger.active span:nth-child(2) {
opacity: 0;
}

.hamburger.active span:nth-child(3) {
transform: rotate(-45deg) translate(7px, -7px);
}

/* --- Responsive --- */
@media (max-width: 1024px) {
.hamburger {
display: flex;
}

.nav-links {
position: absolute;
top: calc(100% + 15px); /* Below the glass pill */
left: 0;
right: 0;
background: rgba(255, 255, 255, 0.98);
backdrop-filter: blur(15px);
flex-direction: column;
padding: 1.5rem;
gap: 1rem;
opacity: 0;
visibility: hidden;
transform: translateY(-10px);
transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
border: 1px solid var(--border-glass);
border-radius: 1.5rem;
box-shadow: 0 15px 35px rgba(0,0,0,0.15);
width: 100%;
z-index: 999;
}

.nav-links.open {
opacity: 1;
visibility: visible;
transform: translateY(0);
}

.header-logos h2 {
font-size: 1rem;
}

.nav-logo {
height: 28px;
}
}

@media (max-width: 480px) {
.header-logos {
gap: 0.5rem;
}
.header-logos h2 {
font-size: 0.85rem;
}
.nav-logo {
height: 22px;
}
.glass-nav {
padding: 0.6rem 1rem;
border-radius: 2rem;
}
}

@media (min-width: 1025px) and (max-width: 1280px) {
.nav-links {
gap: 1rem !important;
}
.header-logos {
gap: 1rem !important;
}
.nav-links a {
padding: 0.5rem 0.75rem !important;
font-size: 0.9rem !important;
}
.nav-logo {
height: 30px !important;
}
}

/* =============================================
CANDIDATE PORTAL – Registration & Dashboard
============================================= */

.candidate-page {
padding: 2rem 1rem 4rem;
min-height: calc(100vh - 120px);
}

.candidate-page-inner {
max-width: 760px;
margin: 0 auto;
display: flex;
flex-direction: column;
gap: 1.5rem;
}

.candidate-page-header {
text-align: center;
margin-bottom: 0.5rem;
}

.candidate-page-header h1 {
font-size: clamp(1.6rem, 4vw, 2.4rem);
font-weight: 700;
margin-bottom: 0.5rem;
}

.candidate-page-header .subtitle {
color: var(--text-secondary);
font-size: 1rem;
}

.candidate-card {
border-radius: var(--radius-lg);
padding: 2rem;
}

.signin-inner {
display: flex;
flex-direction: column;
align-items: flex-start;
gap: 0.5rem;
max-width: 420px;
}

.auth-hint {
color: var(--text-secondary);
font-size: 0.85rem;
line-height: 1.5;
}

.form-section {
margin-bottom: 1.75rem;
}

.form-section:last-of-type {
margin-bottom: 0.5rem;
}

.form-section-title {
font-size: 1rem;
font-weight: 700;
color: var(--text-primary);
margin-bottom: 1rem;
padding-bottom: 0.4rem;
border-bottom: 2px solid var(--brand-maroon);
display: inline-block;
}

.form-group {
margin-bottom: 1rem;
}

.form-group label {
display: block;
font-weight: 600;
font-size: 0.875rem;
color: var(--text-secondary);
margin-bottom: 0.4rem;
}

.label-optional {
font-weight: 400;
color: var(--text-secondary);
font-size: 0.8rem;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group select,
.form-group textarea {
width: 100%;
padding: 0.65rem 0.9rem;
border: 1.5px solid var(--border);
border-radius: var(--radius-md);
background: var(--bg-card);
color: var(--text-primary);
font-family: var(--font-primary);
font-size: 0.9rem;
transition: border-color 0.2s;
box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
outline: none;
border-color: var(--brand-maroon);
}

.form-hint {
font-size: 0.82rem;
color: var(--text-secondary);
margin-bottom: 0.75rem;
}

.checkbox-grid {
display: flex;
gap: 0.75rem;
flex-wrap: wrap;
}

.check-chip {
display: flex;
align-items: center;
gap: 0.4rem;
padding: 0.5rem 1rem;
border-radius: 100px;
border: 1.5px solid var(--border);
background: var(--bg-card);
cursor: pointer;
font-size: 0.875rem;
font-weight: 500;
transition: all 0.2s;
user-select: none;
}

.check-chip input { display: none; }

.check-chip:has(input:checked) {
background: linear-gradient(135deg, var(--brand-maroon) 0%, var(--brand-orange) 100%);
color: white;
border-color: transparent;
box-shadow: var(--shadow-sm);
}

.check-chip:hover { border-color: var(--brand-maroon); }

.file-drop-zone {
border: 2px dashed var(--border);
border-radius: var(--radius-md);
padding: 1.5rem;
text-align: center;
cursor: pointer;
transition: border-color 0.2s;
position: relative;
}

.file-drop-zone:hover { border-color: var(--brand-maroon); }

.file-input {
position: absolute;
inset: 0;
opacity: 0;
cursor: pointer;
width: 100%;
height: 100%;
}

.file-drop-label {
display: flex;
flex-direction: column;
align-items: center;
gap: 0.4rem;
pointer-events: none;
}

.file-icon { font-size: 2rem; }

#cv-filename {
font-size: 0.85rem;
color: var(--text-secondary);
}

.company-picker-grid {
display: grid;
grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
gap: 0.75rem;
}

.company-pick-card {
display: flex;
flex-direction: column;
align-items: center;
gap: 0.5rem;
padding: 0.85rem;
border: 2px solid var(--border);
border-radius: var(--radius-md);
cursor: pointer;
transition: all 0.2s;
background: var(--bg-card);
text-align: center;
}

.company-pick-card input { display: none; }

.company-pick-card:has(input:checked) {
border-color: var(--brand-maroon);
background: rgba(102, 14, 14, 0.06);
box-shadow: 0 0 0 3px rgba(102,14,14,0.1);
}

.company-pick-card.inactive { opacity: 0.5; cursor: not-allowed; }

.company-pick-logo {
width: 56px; height: 56px;
object-fit: contain;
border-radius: 8px;
background: white;
padding: 2px;
}

.company-pick-name {
font-size: 0.75rem;
font-weight: 600;
color: var(--text-primary);
line-height: 1.2;
}

.company-pick-closed {
font-size: 0.7rem;
color: var(--text-secondary);
background: var(--border);
padding: 1px 6px;
border-radius: 4px;
}

.user-info {
display: flex;
align-items: center;
gap: 1rem;
padding: 0.75rem 1rem;
background: var(--bg-card);
border-radius: var(--radius-md);
border: 1px solid var(--border);
margin-bottom: 1.5rem;
}

.user-avatar { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; }
.user-name { font-weight: 700; font-size: 1rem; }
.user-email { font-size: 0.82rem; color: var(--text-secondary); margin: 0; }

.btn-primary {
display: inline-flex;
align-items: center;
justify-content: center;
gap: 0.5rem;
padding: 0.75rem 1.5rem;
border-radius: var(--radius-md);
background: linear-gradient(135deg, var(--brand-maroon) 0%, var(--brand-orange) 100%);
color: white;
font-weight: 600;
font-size: 0.95rem;
border: none;
cursor: pointer;
transition: all 0.2s;
font-family: var(--font-primary);
box-shadow: var(--shadow-sm);
}

.btn-primary:hover {
transform: translateY(-2px);
box-shadow: 0 10px 20px rgba(161, 32, 36, 0.2);
}
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.btn-full { width: 100%; margin-top: 1.25rem; }

.form-error {
padding: 0.75rem 1rem;
background: rgba(220, 38, 38, 0.08);
border: 1px solid rgba(220, 38, 38, 0.3);
border-radius: var(--radius-md);
color: #dc2626;
font-size: 0.875rem;
margin-top: 1rem;
}

.form-flash {
padding: 0.9rem 1.25rem;
border-radius: var(--radius-md);
font-size: 0.9rem;
font-weight: 500;
}

.form-flash--success {
background: rgba(22, 163, 74, 0.1);
border: 1px solid rgba(22, 163, 74, 0.3);
color: #166534;
}

.form-flash--error {
background: rgba(220, 38, 38, 0.08);
border: 1px solid rgba(220, 38, 38, 0.3);
color: #dc2626;
}

.privacy-note {
font-size: 0.78rem;
color: var(--text-secondary);
text-align: center;
margin-top: 0.75rem;
}

/* Consolidated with .nav-links a.cta-nav above */

/* Dashboard */
.dashboard-profile {
display: flex;
align-items: center;
gap: 1rem;
justify-content: center;
flex-wrap: wrap;
}

.dashboard-avatar {
width: 60px; height: 60px;
border-radius: 50%;
object-fit: cover;
border: 3px solid var(--brand-maroon);
}

.dashboard-actions {
display: flex;
gap: 0.6rem;
justify-content: center;
flex-wrap: wrap;
margin-top: 0.5rem;
}

.btn-outline-sm {
padding: 0.35rem 0.85rem;
border-radius: 100px;
border: 1.5px solid var(--border);
color: var(--text-secondary);
font-size: 0.8rem;
font-weight: 500;
cursor: pointer;
transition: all 0.2s;
text-decoration: none;
background: transparent;
}

.btn-outline-sm:hover { border-color: var(--brand-maroon); color: var(--brand-maroon); }
.btn-outline-sm.maroon:hover {
background: linear-gradient(135deg, var(--brand-maroon) 0%, var(--brand-orange) 100%);
border-color: transparent;
color: #ffffff !important;
box-shadow: var(--shadow-sm);
}
.btn-outline-sm.danger:hover { border-color: #dc2626; color: #dc2626; }

.profile-chips {
display: flex;
flex-wrap: wrap;
gap: 0.5rem;
align-items: center;
}

.profile-chip {
background: var(--bg-card);
border: 1px solid var(--border);
border-radius: 100px;
padding: 0.3rem 0.85rem;
font-size: 0.8rem;
font-weight: 500;
color: var(--text-secondary);
}

.cv-chip {
background: linear-gradient(135deg, var(--brand-maroon) 0%, var(--brand-orange) 100%);
color: white !important;
border-color: transparent;
text-decoration: none;
transition: all 0.2s;
box-shadow: var(--shadow-sm);
}

.cv-chip:hover {
transform: translateY(-1px);
box-shadow: 0 4px 12px rgba(161, 32, 36, 0.2);
}

.dashboard-queue-grid {
display: grid;
grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
gap: 1rem;
}

.queue-card {
border: 1.5px solid var(--border);
border-radius: var(--radius-md);
padding: 1rem;
background: var(--bg-card);
display: flex;
flex-direction: column;
gap: 0.75rem;
transition: border-color 0.2s;
}

.queue-card--joined {
border-color: var(--brand-maroon);
background: rgba(102,14,14,0.04);
}

.queue-card--inactive { opacity: 0.55; }

.queue-card-header {
display: flex;
align-items: center;
gap: 0.65rem;
}

.queue-company-logo {
width: 40px; height: 40px;
object-fit: contain;
border-radius: 6px;
background: white;
padding: 2px;
}

.queue-company-info { display: flex; flex-direction: column; }
.queue-company-name { font-weight: 700; font-size: 0.875rem; color: var(--text-primary); }
.queue-table { font-size: 0.75rem; color: var(--text-secondary); }

.queue-state {
font-size: 0.8rem;
font-weight: 600;
padding: 0.3rem 0.75rem;
border-radius: 100px;
display: inline-block;
}

.queue-state--enqueued { background: rgba(234,179,8,0.15); color: #92400e; }
.queue-state--calling,
.queue-state--decision { background: rgba(234,88,12,0.15); color: #c2410c; animation: pulse-badge 1.5s infinite; }
.queue-state--happening { background: rgba(22,163,74,0.15); color: #166534; }
.queue-state--completed { background: rgba(100,116,139,0.15); color: #475569; }
.queue-state--open { background: rgba(14,165,233,0.1); color: #0369a1; }
.queue-state--inactive { background: var(--border); color: var(--text-secondary); }

@keyframes pulse-badge {
0%, 100% { opacity: 1; }
50% { opacity: 0.6; }
}

.queue-btn {
padding: 0.35rem 0.9rem;
border-radius: 100px;
font-size: 0.8rem;
font-weight: 600;
border: none;
cursor: pointer;
transition: all 0.2s;
font-family: var(--font-primary);
}

.queue-btn--join {
background: linear-gradient(135deg, var(--brand-maroon) 0%, var(--brand-orange) 100%);
color: white;
}
.queue-btn--join:hover {
transform: translateY(-1px);
box-shadow: 0 4px 12px rgba(161, 32, 36, 0.2);
}
.queue-btn--leave { background: transparent; border: 1.5px solid var(--border); color: var(--text-secondary); }
.queue-btn--leave:hover { border-color: #dc2626; color: #dc2626; }

.queue-locked-note {
font-size: 0.75rem;
color: var(--text-secondary);
font-style: italic;
}

@media (max-width: 600px) {
.candidate-card { padding: 1.25rem; }
.dashboard-queue-grid { grid-template-columns: 1fr 1fr; }
.company-picker-grid { grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); }
}

/* Registration number badge */
.reg-number-badge {
display: inline-flex;
align-items: center;
gap: 0.5rem;
flex-wrap: wrap;
margin-top: 0.4rem;
font-size: 0.82rem;
font-weight: 600;
color: var(--text-primary);
background: rgba(99, 102, 241, 0.12);
border: 1px solid rgba(99, 102, 241, 0.28);
border-radius: 999px;
padding: 0.25rem 0.75rem;
width: fit-content;
cursor: default;
}
.reg-number-hint {
font-size: 0.72rem;
font-weight: 400;
color: var(--text-secondary);
border-left: 1px solid rgba(99,102,241,0.3);
padding-left: 0.5rem;
}

/* Paused state */
.queue-card--paused { opacity: 0.85; }
.queue-state--paused {
background: rgba(245,158,11,0.15);
color: #d97706;
border-color: rgba(245,158,11,0.3);
}


/* Registration info two-column layout */
.reg-info-grid {
display: grid;
grid-template-columns: 1fr auto 1fr;
gap: 1.25rem;
align-items: start;
}

.reg-info-col {
display: flex;
flex-direction: column;
gap: 0.35rem;
}

.reg-info-col strong {
font-size: 0.9rem;
color: var(--text-primary);
}

.reg-info-col p {
font-size: 0.82rem;
color: var(--text-secondary);
line-height: 1.5;
margin: 0;
}

.reg-info-icon {
font-size: 1.5rem;
margin-bottom: 0.15rem;
}

.reg-info-divider {
width: 1px;
background: var(--border);
align-self: stretch;
margin: 0 0.25rem;
}

@media (max-width: 540px) {
.reg-info-grid {
grid-template-columns: 1fr;
}
.reg-info-divider {
width: auto;
height: 1px;
margin: 0;
}
}

/* ── Registration ID hero block ── */
.reg-id-hero {
display: flex;
align-items: center;
justify-content: space-between;
gap: 1rem;
flex-wrap: wrap;
margin-bottom: 0.25rem;
}

.reg-id-hero__inner {
display: flex;
flex-direction: column;
align-items: flex-start;
gap: 0.35rem;
padding: 1.25rem 1.75rem;
border-radius: var(--radius-xl);
border: 2px solid rgba(99,102,241,0.35);
flex: 1 1 340px;
min-width: 0;
}

.reg-id-hero__label {
font-size: 0.72rem;
font-weight: 700;
letter-spacing: 0.1em;
text-transform: uppercase;
color: var(--text-secondary);
}

.reg-id-hero__number {
font-size: clamp(2.5rem, 8vw, 4rem);
font-weight: 900;
font-family: 'Consolas', monospace;
line-height: 1;
background: linear-gradient(135deg, #6366f1, #a855f7);
-webkit-background-clip: text;
-webkit-text-fill-color: transparent;
background-clip: text;
}

.reg-id-hero__hint {
font-size: 0.8rem;
color: var(--text-secondary);
line-height: 1.55;
max-width: 520px;
}

.reg-id-hero__hint a {
color: var(--brand-maroon);
text-decoration: underline;
text-underline-offset: 2px;
}

.reg-id-hero__actions {
display: flex;
flex-direction: column;
gap: 0.5rem;
flex-shrink: 0;
}

@media (max-width: 560px) {
.reg-id-hero { flex-direction: column; }
.reg-id-hero__actions { flex-direction: row; flex-wrap: wrap; }
}

/* ── Candidate profile bar ── */
.cand-profile-bar {
display: flex;
align-items: flex-start;
justify-content: space-between;
gap: 1.5rem;
flex-wrap: wrap;
padding: 1.25rem 1.5rem;
border-radius: var(--radius-xl);
margin-bottom: 0;
}

.cand-profile-bar__left {
display: flex;
align-items: flex-start;
gap: 1rem;
flex: 1 1 0;
min-width: 0;
}

.cand-profile-bar__info {
display: flex;
flex-direction: column;
gap: 0.15rem;
min-width: 0;
flex: 1;
}

.cand-profile-bar__name-row {
display: flex;
align-items: center;
justify-content: space-between;
gap: 0.75rem;
flex-wrap: nowrap;
}

.cand-profile-name {
font-size: 1.15rem;
font-weight: 700;
color: var(--text-primary);
white-space: nowrap;
overflow: hidden;
text-overflow: ellipsis;
}

.cand-profile-email {
font-size: 0.8rem;
color: var(--text-secondary);
}

/* Registration number on the right of the profile bar */
.cand-reg-id {
display: flex;
flex-direction: column;
align-items: flex-end;
gap: 0.15rem;
flex-shrink: 0;
text-align: right;
}

.cand-reg-id__label {
font-size: 0.68rem;
font-weight: 700;
letter-spacing: 0.08em;
text-transform: uppercase;
color: var(--text-secondary);
}

.cand-reg-id__number {
font-size: clamp(2rem, 6vw, 3rem);
font-weight: 900;
font-family: 'Consolas', monospace;
line-height: 1;
background: linear-gradient(135deg, #6366f1, #a855f7);
-webkit-background-clip: text;
-webkit-text-fill-color: transparent;
background-clip: text;
}

.cand-reg-id__hint {
font-size: 0.72rem;
color: var(--text-secondary);
line-height: 1.4;
max-width: 220px;
}

.cand-reg-id__hint a {
color: var(--brand-maroon);
text-decoration: underline;
text-underline-offset: 2px;
}

@media (max-width: 560px) {
.cand-profile-bar { flex-direction: column; }
.cand-reg-id { align-items: flex-start; text-align: left; }
}


/* Highlight: company currently calling this candidate */
.interviewer--candidate-calling {
box-shadow: 0 0 0 3px #6366f1, var(--glow-calling) !important;
animation: candidate-calling-pulse 1.5s ease-in-out infinite;
order: -1; /* pushed to start of grid */
}
@keyframes candidate-calling-pulse {
0%, 100% { box-shadow: 0 0 0 3px #6366f1, 0 0 18px 4px rgba(99,102,241,0.4); }
50% { box-shadow: 0 0 0 3px #a855f7, 0 0 32px 8px rgba(168,85,247,0.5); }
}

/* Action button row inside each .interviewer card (below status_information) */
.candidate-card-actions {
padding: 0 1rem 0.85rem;
}
.candidate-card-actions .queue-btn { width: 100%; }
.candidate-card-actions .queue-locked-note {
display: block;
text-align: center;
font-size: 0.75rem;
color: var(--text-secondary);
font-style: italic;
padding-bottom: 0.25rem;
}

/* Candidate's own entry in queue dialog */
.candidate--self {
outline: 2px solid #6366f1;
background: rgba(99,102,241,0.12) !important;
font-weight: 700;
}

/*
* ── Visual focus rules (CANDIDATE DASHBOARD ONLY) ─────────────────────────
*
* Scope guard: .candidate-card-actions only exists in candidate_queues.js cards,
* so these rules never fire on queues.php.
*
* 1. Tone down ALL status indicator bars by default (less visual noise for candidate)
* 2. When the candidate IS being called somewhere:
* - Dim every other card heavily
* - Restore the active card to full brightness
*/

/* 2a. When candidate is being called - other cards dim slightly, colours stay visible */
.container_interviewers:has(.interviewer--candidate-calling)
.interviewer:not(.interviewer--candidate-calling) {
opacity: 0.65;
filter: brightness(0.82);
transition: opacity 0.5s ease, filter 0.5s ease;
}
.container_interviewers:has(.interviewer--candidate-calling)
.interviewer:not(.interviewer--candidate-calling) .status_indicator {
filter: none;
}

/* 2b. Restore the active card to full vivid brightness */
.container_interviewers:has(.interviewer--candidate-calling)
.interviewer--candidate-calling {
opacity: 1 !important;
filter: none !important;
}
.container_interviewers:has(.interviewer--candidate-calling)
.interviewer--candidate-calling .status_indicator {
filter: none !important;
}

/* ── Company Dashboard Redesign ── */
.comp-dashboard-container {
display: grid;
grid-template-columns: 1fr 360px;
gap: 2rem;
width: 100%;
align-items: start;
max-width: 1200px;
}

@media (max-width: 1024px) {
.comp-dashboard-container {
grid-template-columns: 1fr;
}
}

.comp-main-content {
display: flex;
flex-direction: column;
gap: 1.5rem;
}

.comp-profile-card {
display: flex;
align-items: center;
gap: 1.5rem;
padding: 1.5rem;
background: var(--bg-card);
border-radius: var(--radius-xl);
border: 1px solid var(--border);
box-shadow: var(--shadow-sm);
}

.comp-profile-card__logo {
width: 80px;
height: 80px;
border-radius: var(--radius-md);
object-fit: contain;
background: var(--bg-secondary);
padding: 8px;
border: 1px solid var(--border);
}

.comp-profile-card__info {
flex-grow: 1;
}

.comp-profile-card__info h1 {
margin: 0;
font-size: 1.5rem;
color: var(--text-primary);
line-height: 1.2;
}

.comp-table-tag {
display: inline-block;
margin-top: 0.5rem;
padding: 0.25rem 0.75rem;
background: rgba(157, 28, 32, 0.08);
color: var(--brand-maroon);
border-radius: 999px;
font-weight: 700;
font-size: 0.85rem;
}

.comp-stats-grid {
display: grid;
grid-template-columns: repeat(2, 1fr);
gap: 1.25rem;
}

.comp-stat-card {
padding: 1.25rem;
background: var(--bg-card);
border-radius: var(--radius-lg);
border: 1px solid var(--border);
text-align: center;
transition: all var(--transition-normal);
box-shadow: var(--shadow-sm);
}

.comp-stat-card:hover {
transform: translateY(-3px);
box-shadow: var(--shadow-md);
border-color: var(--brand-maroon);
}

.comp-stat-card__label {
font-size: 0.75rem;
font-weight: 700;
text-transform: uppercase;
letter-spacing: 0.05em;
color: var(--text-secondary);
}

.comp-stat-card__value {
font-size: 2.25rem;
font-weight: 900;
margin-top: 0.25rem;
color: var(--brand-maroon);
line-height: 1;
}

.comp-live-session {
padding: 2.5rem;
background: var(--bg-card);
border-radius: var(--radius-xl);
border: 1px solid var(--border);
position: relative;
overflow: hidden;
text-align: center;
transition: all var(--transition-normal);
box-shadow: var(--shadow-sm);
}

.comp-live-session--active {
border-color: var(--brand-maroon);
background: linear-gradient(135deg, var(--bg-card) 0%, rgba(157, 28, 32, 0.03) 100%);
box-shadow: var(--shadow-lg);
}

.comp-live-session__label {
font-size: 0.8rem;
font-weight: 800;
text-transform: uppercase;
letter-spacing: 0.1em;
color: var(--text-secondary);
margin-bottom: 1.5rem;
}

.comp-candidate-display {
margin: 2rem 0;
}

.comp-candidate-number {
font-size: 1rem;
font-weight: 800;
color: var(--brand-maroon);
background: rgba(157, 28, 32, 0.1);
padding: 0.35rem 1rem;
border-radius: 999px;
display: inline-block;
}

.comp-candidate-email {
font-size: 1.75rem;
font-weight: 800;
color: var(--text-primary);
margin-top: 0.75rem;
word-break: break-all;
}

.comp-timer-display {
margin-top: 2rem;
}

.comp-timer-large {
font-size: 4.5rem;
font-weight: 900;
font-family: 'Consolas', monospace;
line-height: 1;
margin-bottom: 0.5rem;
letter-spacing: -0.02em;
}

.comp-timer-label {
font-size: 0.75rem;
font-weight: 700;
text-transform: uppercase;
color: var(--text-secondary);
letter-spacing: 0.05em;
}

.comp-sidebar {
display: flex;
flex-direction: column;
gap: 1.5rem;
}

.comp-sidebar-section {
background: var(--bg-card);
border-radius: var(--radius-xl);
border: 1px solid var(--border);
padding: 1.5rem;
box-shadow: var(--shadow-sm);
}

.comp-sidebar-section__title {
font-size: 1.1rem;
font-weight: 800;
margin-bottom: 1.25rem;
padding-bottom: 0.75rem;
border-bottom: 1px solid var(--border);
color: var(--brand-maroon);
}

.comp-queue-item {
display: flex;
justify-content: space-between;
align-items: center;
padding: 0.85rem 1rem;
background: var(--bg-secondary);
border-radius: var(--radius-lg);
margin-bottom: 0.75rem;
border: 1px solid var(--border);
transition: all var(--transition-fast);
}

.comp-queue-item:hover {
background: var(--bg-card);
border-color: var(--brand-maroon);
transform: translateX(4px);
}

.comp-queue-item__info {
display: flex;
flex-direction: column;
gap: 0.15rem;
}

.comp-queue-item__name {
font-weight: 700;
font-size: 0.95rem;
color: var(--text-primary);
}

.comp-queue-item__meta {
font-size: 0.75rem;
color: var(--text-secondary);
font-weight: 500;
}

.comp-status-tag {
font-size: 0.65rem;
font-weight: 900;
text-transform: uppercase;
padding: 0.25rem 0.5rem;
border-radius: 6px;
letter-spacing: 0.02em;
}

.comp-history-item {
display: flex;
justify-content: space-between;
align-items: center;
padding: 0.75rem 1rem;
background: var(--bg-secondary);
border-radius: var(--radius-md);
margin-bottom: 0.5rem;
opacity: 0.85;
}

.comp-history-item__name {
font-weight: 600;
font-size: 0.85rem;
color: var(--text-primary);
}

.comp-history-item__meta {
font-size: 0.7rem;
color: var(--text-secondary);
}

.comp-empty-state {
text-align: center;
padding: 2.5rem 1.5rem;
opacity: 0.7;
}

.comp-empty-state i {
font-size: 2.5rem;
margin-bottom: 1rem;
display: block;
}

.comp-empty-state p {
font-size: 0.95rem;
font-weight: 600;
color: var(--text-secondary);
}

.comp-controls {
margin-top: 1rem;
padding: 2rem;
background: var(--bg-card);
border-radius: var(--radius-xl);
border: 1px solid var(--border);
text-align: center;
box-shadow: var(--shadow-sm);
}

.comp-controls__btn {
padding: 0.85rem 3rem;
font-size: 1.15rem;
font-weight: 800;
min-width: 280px;
}

.comp-controls__hint {
margin-top: 1rem;
font-size: 0.875rem;
color: var(--text-secondary);
}

/* ── Company Dashboard Responsiveness ── */
@media (max-width: 1024px) {
.comp-dashboard-container {
grid-template-columns: 1fr;
gap: 1.5rem;
padding: 1rem;
}

.comp-sidebar {
order: 2; /* Move queue below main content on mobile */
}
}

@media (max-width: 768px) {
.comp-profile-card {
flex-direction: column;
text-align: center;
padding: 1.25rem;
}

.comp-profile-card__logo {
width: 64px;
height: 64px;
}

.comp-profile-card__info h1 {
font-size: 1.25rem;
}

.comp-header-actions {
width: 100%;
margin-top: 0.5rem;
}

.comp-header-actions .btn-outline-sm {
width: 100%;
text-align: center;
}

.comp-stats-grid {
grid-template-columns: 1fr;
gap: 0.75rem;
}

.comp-live-session {
padding: 1.5rem 1rem;
}

.comp-candidate-email {
font-size: clamp(1.2rem, 5vw, 1.75rem);
}

.comp-timer-large {
font-size: clamp(3rem, 15vw, 4.5rem);
}

.comp-controls__btn {
width: 100%;
min-width: unset;
padding: 0.85rem 1.5rem;
}
}

/* ── Superadmin (os.php) Improvements ── */
.superadmin-login-screen {
justify-content: center;
align-items: center;
min-height: 60vh;
padding: 1rem;
}

.superadmin-login-card {
background: var(--bg-card);
border-radius: var(--radius-lg);
padding: 2.5rem;
max-width: 400px;
width: 100%;
border: 1px solid var(--border);
box-shadow: var(--shadow-lg);
}

.superadmin-login-card h2 {
text-align: center;
margin-bottom: 1.5rem;
color: var(--text-primary);
}

.superadmin-login-card input {
padding: 0.75rem;
border-radius: var(--radius-md);
border: 1px solid var(--border);
background: var(--bg-secondary);
color: var(--text-primary);
font-size: 1rem;
width: 100%;
}

.superadmin-tabs {
display: flex;
gap: 0.75rem;
margin-bottom: 2rem;
flex-wrap: wrap;
width: 100%;
}

.superadmin-tabs .tab-btn {
flex: 1;
min-width: 140px;
padding: 0.75rem 1rem;
border-radius: var(--radius-md);
border: 1px solid var(--border);
background: var(--bg-card);
color: var(--text-primary);
font-weight: 600;
cursor: pointer;
font-size: 0.95rem;
transition: all var(--transition-normal);
}

.superadmin-tabs .tab-btn.active {
background: linear-gradient(135deg, var(--brand-maroon) 0%, var(--brand-orange) 100%);
color: #ffffff !important;
border-color: transparent;
box-shadow: var(--shadow-sm);
}

.btn-secondary-sm, .btn-danger-sm {
padding: 0.75rem 1.25rem;
border-radius: var(--radius-md);
border: 1px solid var(--border);
cursor: pointer;
font-size: 0.9rem;
font-weight: 600;
transition: all var(--transition-normal);
}

.btn-secondary-sm {
/* Styles inherited from global light buttons rule */
}

.btn-danger-sm {
background: var(--accent-danger, #ef4444);
border-color: transparent;
}

.btn-secondary-sm:hover {
border-color: var(--brand-maroon);
color: var(--brand-maroon);
}

.btn-danger-sm:hover {
opacity: 0.9;
transform: translateY(-1px);
}

.btn-success-sm {
padding: 0.75rem 1.25rem;
border-radius: var(--radius-md);
background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
color: #ffffff !important;
border: none;
font-weight: 600;
cursor: pointer;
box-shadow: var(--shadow-sm);
transition: all var(--transition-normal);
}

.btn-success-sm:hover {
transform: translateY(-1px);
box-shadow: var(--shadow-md);
}

.superadmin-controls {
display: flex;
gap: 0.75rem;
margin-bottom: 1.5rem;
flex-wrap: wrap;
align-items: center;
}

.superadmin-controls .search-input {
flex: 1;
min-width: 200px;
padding: 0.75rem 1rem;
border-radius: var(--radius-md);
border: 1px solid var(--border);
background: var(--bg-card);
color: var(--text-primary);
font-size: 0.95rem;
transition: all var(--transition-fast);
}

.superadmin-controls .control-actions {
display: flex;
gap: 0.5rem;
flex-wrap: wrap;
}

@media (max-width: 640px) {
.superadmin-controls {
flex-direction: column;
align-items: stretch;
}

.superadmin-controls .control-actions {
display: grid;
grid-template-columns: 1fr 1fr;
gap: 0.5rem;
}

.superadmin-controls .control-actions button {
padding: 0.65rem 0.5rem;
font-size: 0.8rem;
}

.superadmin-tabs {
gap: 0.35rem !important;
margin-bottom: 1rem !important;
}

#admin-dashboard .tab-btn {
flex: 1 1 45% !important;
font-size: 0.8rem !important;
padding: 0.5rem 0.25rem !important;
min-width: unset !important;
}

#admin-dashboard .tab-btn + button,
#admin-dashboard button[onclick*="showChangePassword"],
#admin-dashboard button[onclick*="doLogout"] {
flex: 1 1 45% !important;
font-size: 0.8rem !important;
padding: 0.5rem 0.25rem !important;
}

.superadmin-table-container {
margin: 0 -1.5rem !important;
padding: 0 1.5rem !important;
overflow-x: auto;
-webkit-overflow-scrolling: touch;
}

#table-companies, #table-candidates, #table-operators {
min-width: 600px;
}
}