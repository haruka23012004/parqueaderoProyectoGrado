document.addEventListener('DOMContentLoaded', function() {
    const tipoUsuario = document.getElementById('tipo-usuario');
    
    tipoUsuario.addEventListener('change', function() {
        const tipo = this.value;
        const camposUniv = document.getElementById('campos-universitarios');
        const campoSemestre = document.getElementById('campo-semestre');
        
        // Lógica de visualización
        if (tipo === 'estudiante' ) {
            camposUniv.classList.remove('hidden');
            document.querySelector('[name="codigo_universitario"]').required = true;
            
            if (tipo === 'estudiante') {
                campoSemestre.classList.remove('hidden');
                document.querySelector('[name="semestre"]').required = true;
            } else {
                campoSemestre.classList.add('hidden');
                document.querySelector('[name="semestre"]').required = false;
            }
        } else {
            camposUniv.classList.add('hidden');
            campoSemestre.classList.add('hidden');
            document.querySelector('[name="codigo_universitario"]').required = false;
            document.querySelector('[name="semestre"]').required = false;
        }
    });
});