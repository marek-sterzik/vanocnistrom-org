import $ from "jquery"

const hljs = require('highlight.js/lib/core');
hljs.registerLanguage('json', require('highlight.js/lib/languages/json'));
$(function (){
    $(".hl").each(function (){
        console.log("highlight", this)
        hljs.highlightElement(this)
    })
})
