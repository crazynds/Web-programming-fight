import './bootstrap';
import './easymde';
import Tagify from '@yaireo/tagify';
import confetti from 'canvas-confetti';
import { createApp } from 'vue'

window.Tagify = Tagify;
window.confetti = confetti;

window.Vue = {
    createApp
}


