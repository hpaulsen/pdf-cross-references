pdf-cross-references
====================

Simple php project to read pdf documents and identify cross-references based on NIST naming conventions.

Point web server to 'public' directory.

Requires ruby pdf-reader
  gem install pdf-reader

The following directories must be writable from the web service:
  - /db/sqlite
  - documents
  - documents/text
