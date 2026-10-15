import '../css/app.css';
// The Datastar client ships with the framework, matched to its PHP SDK ('datastar' is an alias set
// by the TinyTank Vite plugin). Page bundles in resources/js/pages/ import the same instance.
import 'datastar';
import { theme } from 'tinytank';

// Light / dark / system: applies the `_theme` signal (templates/_partials/theme-switcher.twig) and saves it.
theme();
