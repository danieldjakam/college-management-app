module.exports =  {
    format: "A3",
    orientation: "landscape",
    border: "5mm",
    header: {
        height: "2mm",
        contents: ''
    },
    footer: {
        height: "2mm",
        contents: {
            first: 'GSB La Semence',
            2: 'GSB La Semence', // Any page number is working. 1-based index
            default: '<span style="color: #444;">{{page}}</span>/<span>{{pages}}</span>', // fallback value
            last: 'GSB La Semence'
        }
    }
};