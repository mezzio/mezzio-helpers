# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.1.0 - 2015-12-06

### Added

- [zendframework/zend-expressive-helpers#1](https://github.com/zendframework/zend-expressive-helpers/pull/1) adds a
  dependency on mezzio/mezzio-router, which replaces the
  dependency on zendframework/zend-expressive. This change means the component
  can be used without Expressive, and also removes a potential circular
  dependency issue in consumers of the package.

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-expressive-helpers#1](https://github.com/zendframework/zend-expressive-helpers/pull/1) removes
  the mezzio/mezzio, replacing it with a dependency on
  mezzio/mezzio-router.

### Fixed

- Nothing.

## 1.0.0 - 2015-12-04

Initial release.

### Added

- `Mezzio\Helper\UrlHelper` provides the ability to generate a URI path
  based on a given route defined in the `Mezzio\Router\RouterInterface`.
  If registered as a route result observer, and the route being used was also
  the one matched during routing, you can provide a subset of routing
  parameters, and any not provided will be pulled from those matched.
- `Mezzio\Helper\ServerUrlHelper` provides the ability to generate a
  full URI by passing only the path to the helper; it will then use that path
  with the current `Psr\Http\Message\UriInterface` instance provided to it in
  order to generate a fully qualified URI.
- `Mezzio\Helper\ServerUrlMiddleware` is pipeline middleware that can
  be registered with an application; it will inject a `ServerUrlHelper` instance
  with the URI composed in the provided `ServerRequestInterface` instance.
- The package also provides factories compatible with container-interop that can
  be used to generate instances.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
