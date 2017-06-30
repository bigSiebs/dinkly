Dinkly Change Log
=================

06.29.17 - 3.15: Change log is introduced. The module-accessible `parameters` array has been refined into three separate entities. Parameters can still be passed around as always, and this update is backwards-compatible with legacy code. However, going forward, use of `$this->fetchGetParams()` and `$this->fetchPostParams()` will be encouraged inside of controller contexts. The parameters that are passed around via `loadModule()` calls are now referred to as `module_params`. Each of these different parameter types also has a filter function that can be overridden in the main Dinkly class as needed to handle any desired post-processing.