import "./jquery";
import "./bootstrap";
import "./axios";
import "./jqueryLibs";
import "./easymde";
import "./highlight";
import Tagify from "@yaireo/tagify";
import confetti from "canvas-confetti";
import "bootstrap-star-rating";
import { createApp } from "vue";

window.Tagify = Tagify;
window.confetti = confetti;

window.Vue = {
    createApp,
};
