<template>
    <div class="container border rounded bg-primary border-primary py-2">
        <h2>Results</h2>
        <table class="table">
            <thead>
                <tr>
                    <th v-for="column in columns">{{ column }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="result in results" :class='result[7] ? "table-success" : "table-danger"'>
                    <td v-for="item in result">{{ item }}</td>
                </tr>
            </tbody>
        </table>
        <button type="button" @click="download" class="btn btn-secondary" :disabled="!columns.length && !results.length">Download CSV</button>
    </div>
</template>

<script>
import {EventBus} from '../app';

export default {
    name: 'ResultsList',
    data () {
        return {
            columns: [],
            results: []
        }
    },
    mounted() {
        EventBus.$on('upload-complete', ({ columns, results }) => {
            this.columns = columns
            this.results = results
        })
    },
    methods: {
        download () {
            const csv = [this.columns.join(',') + '\n']

            this.results.forEach(r => {
                csv.push(r.join(',') + '\n')
            })

            // define the file type to text/csv
            let csvFile = new Blob(csv, {type: 'text/csv'})
            let downloadLink = document.createElement("a")

            downloadLink.download = 'invite-results.csv'
            downloadLink.href = window.URL.createObjectURL(csvFile)
            downloadLink.style.display = "none"

            document.body.appendChild(downloadLink)
            downloadLink.click()
        }
    }
}
</script>
