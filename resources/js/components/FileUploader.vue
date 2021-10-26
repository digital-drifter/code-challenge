<template>
    <div class="container border rounded bg-primary border-primary my-2">
        <div class="input-group py-2">
            <input type="file" class="form-control" id="csv">
            <button type="button" class="btn btn-secondary" @click="upload" :disabled="uploading">Upload</button>
        </div>
    </div>
</template>

<script>
import {EventBus} from '../app';

export default {
    name: 'FileUploader',
    data () {
        return {
            uploading: false
        }
    },
    methods: {
        upload () {
            this.uploading = true

            const input = document.getElementById('csv')
            const file = input.files[0]
            const data = new FormData()

            data.append('csv', file)

            fetch('/api/upload', {
                method: 'post',
                body: data
            })
                .then(resp => resp.json())
                .then(json => {
                    EventBus.$emit('upload-complete', json)
                })
                .finally(() => {
                    this.uploading = false
                })
        }
    }
}
</script>
