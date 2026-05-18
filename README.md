# DPSG Stamm Theme

Ein datenschutzkonformes und barrierefreies WordPress Block-Child-Theme für DPSG Stämme, basierend auf Twenty Twenty-Four.

## Voraussetzungen

| Anforderung | Version |
|---|---|
| WordPress | 6.6 oder höher |
| PHP | 7.4 oder höher |
| Parent-Theme | Twenty Twenty-Four (muss aktiv installiert sein) |

## Installation

### Per WordPress-Backend
1. [dpsg-stamm-theme.zip herunterladen](https://github.com/ahobitt/dpsg-theme/releases/latest/download/dpsg-stamm-theme.zip)
2. **Theme-ZIP hochladen**: Gehe zu *Design → Designs → Design hochladen* und lade `dpsg-stamm-theme.zip` hoch.
3. **Theme aktivieren**: Klicke auf „Aktivieren".
4. **Setup-Assistent**: Nach der Aktivierung erscheint ein Admin-Hinweis. Wähle, ob Beispielinhalte (Seiten, Demo-Beiträge) automatisch angelegt werden sollen oder ob du mit einem leeren Theme startest.

> **Wichtig:** Der Theme-Ordner muss exakt `dpsg-stamm-theme` heißen, damit interne Pfade korrekt aufgelöst werden. Beim Upload über das WordPress-Backend ist das automatisch der Fall.

### Live-Demo im Browser
👉 [In WordPress Playground öffnen]([https://playground.wordpress.net/#%7B%22landingPage%22%3A%22%2F%22%2C%22steps%22%3A%5B%7B%22step%22%3A%22installTheme%22%2C%22themeZipFile%22%3A%7B%22resource%22%3A%22url%22%2C%22url%22%3A%22https%3A%2F%2Fgithub.com%2Fahobitt%2Fdpsg-theme%2Freleases%2Flatest%2Fdownload%2Fdpsg-stamm-theme.zip%22%7D%2C%22options%22%3A%7B%22activate%22%3Atrue%7D%7D%5D%7D](https://playground.wordpress.net/#%7B%22landingPage%22%3A%22%2F%22%2C%22steps%22%3A%5B%7B%22step%22%3A%22installTheme%22%2C%22themeZipFile%22%3A%7B%22resource%22%3A%22url%22%2C%22url%22%3A%22https%3A%2F%2Fdownloads.wordpress.org%2Ftheme%2Ftwentytwentyfour.1.4.zip%22%7D%2C%22options%22%3A%7B%22activate%22%3Afalse%7D%7D%2C%7B%22step%22%3A%22installTheme%22%2C%22themeZipFile%22%3A%7B%22resource%22%3A%22url%22%2C%22url%22%3A%22https%3A%2F%2Fgithub.com%2Fahobitt%2Fdpsg-stamm%2Freleases%2Flatest%2Fdownload%2Fdpsg-stamm-theme.zip%22%7D%2C%22options%22%3A%7B%22activate%22%3Atrue%7D%7D%5D%7D))

## Funktionen

- **Lokale Schriften (Roboto)** – keine externen Google Fonts-Anfragen, DSGVO-konform
- **Emojis deaktiviert** – verhindert externe Anfragen an `s.w.org`
- **DPSG-Farbpalette** – DPSG Blau (`#003056`) und DPSG Rot (`#e02030`) als Theme-Presets
- **Block Patterns** – vorgefertigte Inhaltsmuster für Startseite, Leitungsteam, Termine, FAQ, Downloads und Schutzkonzept
- **Fallback-Beitragsbild** – bei fehlendem Featured Image wird automatisch ein Platzhalterbild eingeblendet
- **Setup-Assistent** – optionale Beispielinhalte bei Aktivierung (mit Nonce-Schutz)

## Logo einrichten

Nach der Aktivierung das DPSG-Logo über *Design → Editor → Seite* im Header-Bereich per Site-Logo-Block setzen:

1. Im Block Editor: Header anklicken → Logo-Block auswählen → Bild hochladen oder aus der Mediathek wählen.
2. Empfohlene Datei: `assets/images/logo.svg` (im Theme-Ordner enthalten, kann über die Mediathek hochgeladen werden).

## Navigation einrichten

Die Header- und Footer-Navigation sind bewusst leer vorinstalliert, damit keine falschen Verlinkungen entstehen.

1. Gehe zu *Design → Editor → Navigation*.
2. Lege eine neue Navigation an und verlinke deine Seiten.
3. Die Navigation erscheint automatisch im Header und Footer.

## Inhalte anpassen

Die generierten Seiten (Impressum, Datenschutzerklärung etc.) sind als **Entwürfe** angelegt und müssen vor der Veröffentlichung mit den echten Stammes-Daten befüllt werden. Das Impressum und die Datenschutzerklärung sind **rechtlich verpflichtend** – bitte mit dem Vorstand oder dem Datenschutzbeauftragten des e.V. abstimmen.

## Lizenz

Der Theme-Code steht unter der **GNU General Public License v2.0 oder höher**.  
Weitere Informationen: https://www.gnu.org/licenses/gpl-2.0.html

### Lizenzierung von Assets

Das in diesem Theme enthaltene **DPSG-Logo** (`assets/images/logo.svg`) unterliegt **nicht** der GPL-Lizenz.  
Es ist Eigentum der **Deutschen Pfadfinderschaft Sankt Georg (DPSG)** und darf ausschließlich von autorisierten DPSG-Gruppen (Stämme, Bezirke, Diözesen) verwendet werden.  
Eine Nutzung durch Dritte ohne ausdrückliche Genehmigung der DPSG ist nicht gestattet.

Weitere Informationen zur Markenpolitik der DPSG: https://dpsg.de

### Verwendete Drittkomponenten

| Asset | Lizenz | Quelle |
|---|---|---|
| Roboto (Regular & Bold) | Apache License 2.0 | Google Fonts / lokal eingebunden |
| Platzhalterfotografien (`placeholder-*.jpg`) | Bitte vor Veröffentlichung durch eigene Fotos ersetzen | — |

## Autor

Eric Schümann – [schuemann.it](https://schuemann.it)
