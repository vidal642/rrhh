import axios from '../plugins/axios';

export default {
    /**
     * Obtener lista de adelantos.
     * @param {Object} params - Filtros (ej. { empleado_id: 1, estado: 'Pendiente' })
     */
    obtenerAdelantos(params = {}) {
        return axios.get('/adelantos', { params });
    },

    /**
     * Crear un nuevo adelanto.
     * @param {Object} datos - { empleado_id, monto, fecha, descripcion }
     */
    crearAdelanto(datos) {
        return axios.post('/adelantos', datos);
    },

    /**
     * Obtener un adelanto por su ID.
     * @param {number} id 
     */
    obtenerAdelanto(id) {
        return axios.get(`/adelantos/${id}`);
    },

    /**
     * Actualizar un adelanto.
     * @param {number} id 
     * @param {Object} datos 
     */
    actualizarAdelanto(id, datos) {
        return axios.put(`/adelantos/${id}`, datos);
    },

    /**
     * Eliminar un adelanto.
     * @param {number} id 
     */
    eliminarAdelanto(id) {
        return axios.delete(`/adelantos/${id}`);
    }
};
