# Awful

WordPress development, made more awful.

[![Build Status](https://travis-ci.org/GiacoCorsiglia/awful.svg?branch=master)](https://travis-ci.org/GiacoCorsiglia/awful)
[![Coverage Status](https://coveralls.io/repos/github/GiacoCorsiglia/awful/badge.svg)](https://coveralls.io/github/GiacoCorsiglia/awful)

**Very much a work in progress.**

## TODO

### Models

- [ ] Complete implementation of `Model::get()` and `SubModel::get()`.
    - [ ] Ensure they fetch data in the correct site context (for multisite).
- [ ] Complete implementation of builtin models and drop in/implement getters for builtin model fields
    - [ ] Post types
        - [ ] `post`
        - [ ] `page`
        - [ ] `attachment`: probably add special case for images.
        - [ ] `nav_menu_item`: Either this or create a separate class for menus
    - [ ] Taxonomies & Terms
        - [ ] Drop in and review implementation
    - [ ] Comments
    - [ ] Site
        - [ ] Implement options pages.
        - [ ] Consider adding `Site::getPosts()` and similar.
    - [ ] Users
        - [ ] Disallow setting of passwords or usernames
    - [ ] Network
- [ ] Complete implementation of `Model`/`SubModel` `::set()`, `::save()`, `::update()`
    - [ ] Ensure they save data in the correct site context (for multisite).
- [ ] Design & implement Query sets
    - [ ] Design pagination wrapper

### Bootstrapping

- [ ] Complete design and implementation of `ModelRegistrar`
    - [ ] Post types
    - [ ] Custom taxonomies?
- [ ] Complete design and implementation of `FieldsRegistrar`

### Container

- [ ] Settle on or build a container which doesn't rely on reflection on production.  Should support circular dependencies via setters or `ChainedDependencies` or something.
    - [ ] Benchmark this to see if it really matters.
- [ ] Determine if contextual container bindings are ever necessary.
- [ ] Consider `Container::extend()`, `::clone()` or equivalent.

### Routing & Controllers

- [ ] Decide on router configuration method.
- [ ] Consider adding the ability to disable WP rewrite entirely.
- [ ] Drop in and review controller implementation

### Templating

- [ ] Finalize `TemplateEngine` interface.
- [ ] Determine configuration for e.g. registering twig extensions.

### General

- [ ] Determine configuration for directory paths for caching.
- [ ] Add test cases with and without multisite enabled

## NOTES
