import $ from "jquery"

const hljs = require('highlight.js/lib/core');
hljs.registerLanguage('json', require('highlight.js/lib/languages/json'));
hljs.registerLanguage('bash', require('highlight.js/lib/languages/bash'));
$(function (){
    $(".hl").each(function (){
        console.log("highlight", this)
        hljs.highlightElement(this)
    })
})
