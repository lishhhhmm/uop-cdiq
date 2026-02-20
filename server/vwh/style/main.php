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
color: #ffffff;
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

& #current_url_qr {
aspect-ratio: 1 / 1;
object-fit: cover;
width: clamp(0px, 75vw, 256px);
border-radius: var(--radius-xl);
border: 2px solid var(--border);
background: var(--bg-card);
padding: 1rem;
}

& p {
color: var(--text-secondary);
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

.cta-nav {
background: linear-gradient(135deg, var(--accent-red), var(--accent-orange)) !important;
padding: 0.6rem 1.5rem !important;
border-radius: 25px !important;
color: white !important;
box-shadow: 0 4px 15px rgba(204, 43, 46, 0.4);
font-weight: 600 !important;
border: none !important;
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
@media (max-width: 800px) {
.hamburger {
display: flex;
}

.nav-links {
position: fixed;
top: 70px; /* Below glass nav */
left: 0;
right: 0;
background: rgba(255, 255, 255, 0.95);
backdrop-filter: blur(15px);
flex-direction: column;
padding: 1.5rem;
gap: 1rem;
transform: translateY(-150%);
transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
border-bottom: 1px solid var(--border-glass);
box-shadow: 0 10px 30px rgba(0,0,0,0.1);
width: 100%;
}

.nav-links.open {
transform: translateY(0);
}

.header-logos h2 {
font-size: 1rem;
}

.nav-logo {
height: 28px;
}
}