const $formRotinas = $("#formRotinas")
console.log("🚀 ~ $formRotinas:", $formRotinas)

$("#formRotinas").on("submit", (e)=>{
    e.preventDefault()
    console.log("SUBMIT")
    gravarFrm("formRotinas")
})