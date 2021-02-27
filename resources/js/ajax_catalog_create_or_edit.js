import Form from '../../../js/core/Form';
import swal from 'sweetalert2';

// __Форма создания и редактирования страницы каталога
if (document.querySelector('#ajaxCatalogPageForm')) {
    let reset = false;
    if (location.pathname.match(/create/)) {
        reset = true;
    }
    let vm = new Vue({
        el: '#ajaxCatalogPageForm',
        data: {
            form: new Form({
                name: '',
                title: '',
                description: '',
                menu: '',
                link: '',
                content: '',
            }, reset),
            seo: false,
        },
        computed: {
            titleLength(){
                return this.form.title.length + '/55';
            },
            linkLength(){
                return this.form.raw_url.length + '/255';
            },
            menuLength(){
                return this.form.menu.length + '/255';
            },
            descriptionLength(){
                return this.form.description.length + '/210';
            },
        },
        components: {
            fieldTextarea: require('../../../js/components/Textarea.vue'),
            fieldInput: require('../../../js/components/Input.vue'),
            Ckeditor,
        },
        methods: {
            onSubmit() {
                const self = this;
                this.form.submit(this.$el.method, this.$el.action)
                    .then(res => {
                        if (!vm.form.errors.any()) {

                            swal({
                                title: successMessage,
                                type: 'info',
                                focusConfirm: true,
                                confirmButtonText:
                                successButton,
                            })
                        }
                    }).catch(rej => console.log(rej));
            },
            showSeo() {
                return this.seo = !this.seo;
            },
        },
        created() {
            let hasCategory = Object.keys(item).length;

            this.form.name = hasCategory ? item['name'] : '';
            this.form.title = hasCategory ? item['seo'][0]['title'] : '';
            this.form.description = hasCategory ? item['seo'][0]['description'] : '';
            this.form.menu = hasCategory ? item['seo'][0]['menu'] : '';
            this.form.link = hasCategory ? item['link'] : '';
            this.form.content = hasCategory ? item['text'] : '';
        },
        mounted() {
            console.log('Form ready');
        }
    });
}