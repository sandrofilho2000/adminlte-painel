$(function () {
    const campoUsuario = $("#id_usuario");
    const campoPermissao = $("#id_permissao");

    function preencherPermissoes(rotinas) {
        limparPermissoes();

        rotinas.forEach(function (rotina) {
            campoPermissao.append(
                new Option(`(${rotina.Rotina}) ${rotina.Descricao}` || "", rotina.id || "", false, false)
            );
        });

        campoPermissao.val("").trigger("change");
    }

    iniciarSelect2(campoUsuario, "Selecione um usuario");    

    $("#formUsuarioPermissao").on("submit", function (e) {
        e.preventDefault()
        const formData = new FormData(this)
        const Usuarios = $("#id_usuario").val() || []
        formData.append("Usuarios", Usuarios)
        requestAjax(
            formData, function(result){
                console.log("🚀 ~ result:", result)
            }
        )
    });
});
