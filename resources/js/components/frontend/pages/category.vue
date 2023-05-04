<template>
    <div class="sg-page-content">
        <section class="sg-category">
            <div class="container">
                <div class="title justify-content-between">
                    <h1 v-if="is_shimmer">{{lengthCounter(categoriesData) > 0 ? lang.all_categories : lang.no_category_founds}}</h1>
                    <h1 v-else>{{lang.content_loading}}</h1>
                </div>
                <div class="row" v-if="is_shimmer">
                    <div  class="col-lg-4 col-md-6" v-for="(category,index) in categoriesData" :key="index">
                            <div class="category-style-2">
                                <h6><a :href="getUrl('category/'+category.id)" @click.prevent="routerNavigator('product.by.category',category.slug)">{{ category.name }}</a></h6>
                                <div class="category-list">
                                    <ul class="global-list" v-if="category.child_categories && category.child_categories.length > 0">
                                        <li v-for="(child_category,child_index) in category.child_categories" :key="child_index" :class="{ 'has-baby' : child_category.categories.length > 0 }">
                                            <a :href="getUrl('category/'+child_category.slug)" @click.prevent="routerNavigator('product.by.category',child_category.slug)">{{ child_category.title }}</a>
                                            <ul v-if="child_category.categories.length > 0">
                                                <li v-for="(category,index) in child_category.categories"  :key="'child_category'+index"><a :href="getUrl('category/'+category.slug)" @click.prevent="routerNavigator('product.by.category',category.slug)">{{ category.name }}</a></li>
                                            </ul>
                                        </li>
                                    </ul>
                                    <ul v-else>
                                        <li class="text-white no-before"></li>
                                    </ul>
                                    <div class="category-thumb">
                                        <a :href="getUrl('category/'+category.id)" @click.prevent="routerNavigator('product.by.category',category.id)">
                                            <img loading="lazy" :src="category.media[0].url" :alt="category.name" class="img-fluid">
                                        </a>
                                    </div>
                                </div>
                            </div>

                    </div>
                </div><!-- /.row -->
                <div class="row" v-else-if="shimmer">
                    <div class="col-lg-4 col-md-6" v-for="(category,index) in 6" :key="index">
                        <shimmer :height="238" class="mb-3"></shimmer>
                    </div>
                </div><!-- /.row -->
            </div><!-- /.container -->
        </section><!-- /.sg-category -->
    </div>
</template>

<script>
import shimmer from "../partials/shimmer";
export default {
    name: "category",
    data()
    {
        return {
            next_page_url: false,
            is_shimmer  : false,
            categoriesData: null,
        }
    },
    components:{
        shimmer
    },
    mounted() {
        if(this.lengthCounter(this.categories) == 0){
            this.getCategories;
        }
        if(this.lengthCounter(this.categories)  > 0){
            this.is_shimmer = true
        }
    },
    computed : {
        getCategories(){
            let url = 'https://labsny.net/api/' + 'categories';
            if(this.page !=1){
                this.loading = true;
            }
            this.$Progress.start();
            axios.get(url).then((response) => {
                this.is_shimmer = true
                if (response.data.error) {
                    this.$Progress.fail();
                    toastr.error(response.data.error, this.lang.Error +' !!' );
                } else {
                    if(this.page != 1){
                        this.loading = false
                    }
                    this.categoriesData = response.data.data
                    this.next_page_url = response.data.categories.next_page_url;
                    this.page++;
                    this.$Progress.finish();
                }
            }).catch((error)=>{
                this.loading = false;
                this.is_shimmer = true
                this.$Progress.fail();

                if (error.response && error.response.status == 422)
                {
                    toastr.error(response.data.error, this.lang.Error +' !!' );
                }
            })
        }
    },
    methods : {
        getUrlImage(url){
            return url.replace(/\\/g, '');
        }
    }
}
</script>
