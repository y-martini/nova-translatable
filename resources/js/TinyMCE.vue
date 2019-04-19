<template>
    <editor v-model="editorValue"
            :id="attribute"
            :api-key="apiKey"
            :init="options"
            @onChange="onChange"
            @onInit="onInit"
    />
    <!--:class="errorClasses"-->
</template>

<script>
    // import { FormField, HandlesValidationErrors } from 'laravel-nova'
    import Editor from '@tinymce/tinymce-vue'

    export default {
        components: { Editor },

        // mixins: [FormField, HandlesValidationErrors],

        props: ['value', 'attribute', 'options', 'apiKey'],

        data() {
            return {
                editorValue: ''
            }
        },

        mounted() {
            this.editorValue = this.value || '';

            if (this.options.use_lfm) {
                this.options['file_browser_callback'] = this.filemanager
            }
        },

        methods: {
            update() {
                this.editorValue = this.value ? this.value : '';
            },

            filemanager(field_name, url, type, win) {
                let x = window.innerWidth || document.documentElement.clientWidth || document.getElementsByTagName('body')[0].clientWidth;
                let y = window.innerHeight|| document.documentElement.clientHeight|| document.getElementsByTagName('body')[0].clientHeight;

                let cmsURL = this.options.path_absolute + this.options.lfm_url + '?field_name=' + field_name;
                if (type === 'image') {
                    cmsURL = cmsURL + '&type=Images';
                } else {
                    cmsURL = cmsURL + '&type=Files';
                }

                tinyMCE.activeEditor.windowManager.open({
                    file : cmsURL,
                    title : 'Filemanager',
                    width : x * 0.8,
                    height : y * 0.8,
                    resizable : 'yes',
                    close_previous : 'no'
                });
            },

            onInit() {
                this.editorValue = this.value || '';
            },

            onChange() {
                this.$emit('change', this.editorValue)
            },
        }
    }
</script>
