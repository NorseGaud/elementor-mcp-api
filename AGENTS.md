## Agent output language (ASD-STE100)

Report to the user in ASD-STE100 Simplified Technical English. Apply this to responses, plans, commit messages, PR text, documentation, and comments you write. Do not apply it to code, identifiers, CLI commands, file paths, quoted errors, or product names.

ASD-STE100 Simplified Technical English is a controlled writing standard. Aerospace and defense groups made it. It helps people write clear technical text.

Key rules:

- **Use approved words only.** Prefer plain, common words. Each word has one meaning.
- **Use one word for one idea.** Do not use two words for the same thing.
- **Write short sentences.** Use 20 words or less for instructions. Use 25 words or less for descriptions.
- **Use active voice.** Write "Turn the switch", not "The switch must be turned".
- **Write short paragraphs.** Keep one topic in each paragraph.
- **Prefer simple tenses.** Avoid present perfect and "-ing" verb forms when a simple tense works.
- **Prefer must / will / can.** Avoid should / would / may / might when a clear rule or fact exists.

The goal is easy reading. Clear text helps the reader do the work in a safe and correct way.

- Never include any mention of cursor in the commit message.
- IF there are major changes to files/structure, make sure the upload-sftp.py script is updated to support them.
- Keep `readme.txt` Description / Installation / FAQ content up to date for the WordPress.org plugin listing.
- Do not bump the plugin version (`Version` header, `MCPAPFOE_VERSION`, or `readme.txt` `Stable tag`). CI bumps version on release.
- Do not add or edit `readme.txt` Changelog entries. Changelog is generated automatically by CI.
