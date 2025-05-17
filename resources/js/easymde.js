import EasyMDE from "easymde";

window.onload = function () {
    var markdowns = document.getElementsByClassName("markdown");
    for (var i = 0; i < markdowns.length; i++) {
        const easyMDE = new EasyMDE({
            element: markdowns[i],
            maxHeight: "300px",
            spellChecker: false,
            autosave: {
                enabled: true,
                delay: 3000,
                uniqueId: markdowns[i].id,
                timeFormat: {
                    locale: "pt-BR",
                    format: "hour:minute:second",
                },
                binded: true,
            },
            previewClass: ["mathjax", "editor-preview"],
            renderingConfig: {
                sanitizerFunction: (val) => {
                    setTimeout(() => {
                        MathJax.typesetPromise();
                    }, 50);
                    return val;
                },
            },
        });
        const value = markdowns[i].value.trim();
        if (value.length > 0) easyMDE.value(markdowns[i].value);
    }
};
