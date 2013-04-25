pdf-cross-references
====================

Simple php project to read pdf documents and identify cross-references based on NIST naming conventions.

Point web server to 'public' directory.

Requires ruby pdf-reader, so first install ruby (if not already installed) and then run the following in a command-line
interface:
  gem install pdf-reader

Create the following directories (if they don't already exist) and make sure that the web service can write to them:
  - /db/sqlite
  - documents
  - documents/text
