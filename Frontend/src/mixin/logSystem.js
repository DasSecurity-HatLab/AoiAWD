export default {
    beforeMount() {
        let page = this.$route.query.page;
        let highLight = this.$route.query.id;
        if (page) {
          this.page = parseInt(page);
          this.changePage(parseInt(page));
          this.highLight = highLight;
        } else {
          this.changePage(1);
        }
    },
    methods: {
        gotoLatestGenerator(ajaxPromise, vuexAction) {
            return () => {
                return this.changePageGenerator(ajaxPromise, vuexAction, null, () => {this.page = this.lastPage})();
            }
        },
        changePageGenerator(ajaxPromise, vuexAction, path, callback) {
            return () => {
                this.loading = true;
                ajaxPromise.then(res => {
                    console.log(res);
                    this.$store.dispatch(vuexAction, {
                        logs: res.data.data
                    });
                    this.lastPage = res.data.last_page;
                    this.loading = false;
                    if (path) {
                        this.$router.push({
                            path,
                            query: {
                                page: this.page
                            }
                        });
                    }
                    if (typeof callback == 'function') {
                        callback();
                    }
                })
            }
        }
    }
};
