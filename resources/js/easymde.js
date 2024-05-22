import EasyMDE from 'easymde';

window.onload = function(){
    var markdowns = document.getElementsByClassName("markdown");
    for (var i = 0; i < markdowns.length; i++) {
        const easyMDE = new EasyMDE({
            element: markdowns[i],
            maxHeight: '300px',
            spellChecker: false,
            autosave: {
                enabled: true,
                delay: 3000,
                uniqueId: markdowns[i].id,
                timeFormat:{
                    locale: 'pt-BR',
                    format: 'hour:minute:second'
                },
                binded: true,
            },
            previewClass: ['mathjax','editor-preview'],
            renderingConfig:{
                sanitizerFunction:(val)=>{
                    setTimeout(()=>{
                        MathJax.typesetPromise()
                    },50)
                    return val;
                }
            }
        });
        const value = markdowns[i].value.trim();
        if(value.length>0)
            easyMDE.value(markdowns[i].value)
        
    }
}

// EasyMDE.prototype.autosave = function () {
//     if (isLocalStorageAvailable()) {
//         var easyMDE = this;

//         if (this.options.autosave.uniqueId == undefined || this.options.autosave.uniqueId == '') {
//             console.log('EasyMDE: You must set a uniqueId to use the autosave feature');
//             return;
//         }

//         if (this.options.autosave.binded !== true) {
//             if (easyMDE.element.form != null && easyMDE.element.form != undefined) {
//                 easyMDE.element.form.addEventListener('submit', function () {
//                     clearTimeout(easyMDE.autosaveTimeoutId);
//                     easyMDE.autosaveTimeoutId = undefined;
//                 });
//             }

//             this.options.autosave.binded = true;
//         }

//         if (this.options.autosave.loaded !== true) {
//             if (typeof localStorage.getItem('smde_' + this.options.autosave.uniqueId) == 'string' && localStorage.getItem('smde_' + this.options.autosave.uniqueId) != '') {
//                 this.codemirror.setValue(localStorage.getItem('smde_' + this.options.autosave.uniqueId));
//                 this.options.autosave.foundSavedValue = true;
//             }

//             this.options.autosave.loaded = true;
//         }

//         var value = easyMDE.value();
//         if (value !== '') {
//             localStorage.setItem('smde_' + this.options.autosave.uniqueId, value);
//         } else {
//             localStorage.removeItem('smde_' + this.options.autosave.uniqueId);
//         }

//         var el = document.getElementById('autosaved');
//         if (el != null && el != undefined && el != '') {
//             var d = new Date();
//             var dd = new Intl.DateTimeFormat([this.options.autosave.timeFormat.locale, 'en-US'], this.options.autosave.timeFormat.format).format(d);
//             var save = this.options.autosave.text == undefined ? 'Autosaved: ' : this.options.autosave.text;

//             el.innerHTML = save + dd;
//         }
//     } else {
//         console.log('EasyMDE: localStorage not available, cannot autosave');
//     }
// };