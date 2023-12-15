import {Terminal} from "xterm"
import {FitAddon} from "xterm-addon-fit"
import $ from "jquery"

function writeToTerm(term, first)
{
    setTimeout(() => {
        const color = first ? 31 : 32
        term.write('Hello from \x1B[1;3;'+color+'mxterm.js\x1B[0m $\n\r')
        for (var i = 0; i < 100; i++) {
            term.write("word"+i+" ")
        }
        term.write("\n\r")
    }, 300)
}

const defaultConfig = {
    optimalFit: null,
    minimalFit: null,
    baseFontSize: 17,
    resetOnResize: false,
    dataEndpoint: null,
    noDataTimeout: 500,
    errorTimeout: 500
}

function createConfig(config)
{
    if (config) {
        config = JSON.parse(config)
    } else {
        config = {}
    }
    config = {...defaultConfig, ...config}
    return config
}

function calcFontFit(cellRatio, parentSize, fitConfig)
{
    if (fitConfig === null) {
        return null
    }
    const parentX = parentSize[0]
    const parentY = parentSize[1]
    const nX = fitConfig[0]
    const nY = fitConfig[1]
    const ratioX = cellRatio[0]
    const ratioY = cellRatio[1]
    const sizeX = (parentX/nX)/ratioX
    const sizeY = (parentY/nY)/ratioY
    return Math.min(sizeX, sizeY)
}

function calcFontSize(cellRatio, parentSize, config)
{
    const optimalFit = calcFontFit(cellRatio, parentSize, config.optimalFit)
    const minimalFit = calcFontFit(cellRatio, parentSize, config.minimalFit)
    const baseFontSize = config.baseFontSize
    if (optimalFit !== null && optimalFit > baseFontSize) {
        return optimalFit
    }
    if (minimalFit !== null && minimalFit < baseFontSize) {
        return minimalFit
    }
    return baseFontSize
}

function setupFontSize(term, parentSize, config)
{
    const cell = term._core._renderService.dimensions.css.cell
    const cellRatio = [cell.width/term.options.fontSize, cell.height/term.options.fontSize]
    term.options.fontSize=calcFontSize(cellRatio, parentSize, config)
}

function getParentSize(element)
{
    const parentElement = element.parent()
    return [parentElement.width(), parentElement.height()]
}

function getTermSize(term)
{
    return "" + term.rows + "x" + term.cols
}

function makeRequest(request)
{
    var endpoint = request.endpoint.replace(/#.*$/, '')
    const lastChar = endpoint.substr(endpoint.length - 1, 1)
    var delim
    if (lastChar === '?' || lastChar === '&') {
        delim = ''
    } else if (endpoint.match(/\?/)) {
        delim = '&'
    } else {
        delim = '?'
    }
    endpoint += delim + "revision=" + encodeURIComponent(request.revision)
    endpoint += "&cols=" + encodeURIComponent(request.cols)
    endpoint += "&rows=" + encodeURIComponent(request.rows)

    return $.get(endpoint, () => null, "json")
}

function startWritingThread(term, runtimeData, config)
{
    const restartNumber = runtimeData.restartNumber
    const dataEndpoint = config.dataEndpoint
    if (dataEndpoint !== null) {
        const dataRequest = {endpoint: dataEndpoint, cols: term.cols, rows: term.rows, revision: runtimeData.revision}
        const promise = makeRequest(dataRequest)
        promise.catch((e) => {
            console.error(e)
            if (runtimeData.restartNumber == restartNumber) {
                startWritingThreadDelayed(term, runtimeData, config, config.errorTimeout)
            }
        }).then((response) => {
            if (runtimeData.restartNumber == restartNumber) {
                runtimeData.revision = response.revision
                const data = response.data
                const delay = (data === null) ? 500 : 0
                if (data !== null) {
                    term.write(data)
                }
                startWritingThreadDelayed(term, runtimeData, config, config.noDataTimeout)
            }
        })
    }
}

function startWritingThreadDelayed(term, runtimeData, config, delay)
{
    if (delay > 0) {
        setTimeout(() => startWritingThread(term, runtimeData, config), delay)
    } else {
        startWritingThread(term, runtimeData, config)
    }
}

$(function(){
    $(".terminal").each(function(){
        const self = $(this)
        const terminalElement = self.get(0)
        const config = createConfig(self.attr("data-config"))
        const term = new Terminal({"scrollback": 0, "fontSize": 17})
        const fitAddon = new FitAddon()
        const runtimeData = {revision: null, restartNumber: 0}
        term.loadAddon(fitAddon)
        term.open(terminalElement)
        setupFontSize(term, getParentSize(self), config)
        fitAddon.fit()
        addEventListener("resize", (event) => {
            const oldSize = getTermSize(term)
            setupFontSize(term, getParentSize(self), config)
            fitAddon.fit()
            const newSize = getTermSize(term)
            if (config.restartOnResize && oldSize != newSize) {
                term.clear()
                runtimeData.revision = null
                runtimeData.restartNumber++
                startWritingThread(term, runtimeData, config)
                
            }
        });
        startWritingThread(term, runtimeData, config)
    })
})

window.Terminal = Terminal
window.FitAddon = FitAddon

