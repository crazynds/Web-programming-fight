import './bootstrap';
import './easymde';
import Tagify from '@yaireo/tagify';

window.Tagify = Tagify;
window.MathJax= {
    processClass: "mathjax",
    ignoreClass: "no-mathjax",
    tex: {
        inlineMath: [['$', '$']]
    }
}


