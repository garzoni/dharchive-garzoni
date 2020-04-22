/**
  * This function serves as a single point of instantiation for a {@link Seanote.Viewer},
  * including all combinations of out-of-the-box configurable features.
  *
  * @function Seanote
  * @o {Seanote.Options} options - Viewer options.
  * @returns {Seanote.Viewer}
  */
window.Seanote = window.Seanote || function(options, i18n) {
    Seanote.loadDictionary(i18n);
    return new Seanote.Viewer(options);
};
