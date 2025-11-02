<script setup>
import { ref } from 'vue';
import { createPinia, setActivePinia } from 'pinia'
import { useRouter } from 'vue-router';
import { useGroupStore } from '../stores/groupStore';


const name = ref('');

setActivePinia(createPinia());
const groupStore = useGroupStore();
const router = useRouter();

const addGroup = async () => {
  const success = await groupStore.createGroup({
    name: name.value,
  });
  if (success) {
    router.push({ name: 'groups' });
  }
};

</script>
<template>
  <div  class="content">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-12">

          <div class="card w-90 p-4">
            <div class="card-body d-flex justify-content-between align-items-center">
              <h4>Group Create</h4>
              <RouterLink :to="{ name: 'groups'}" class="btn btn-sm btn-primary">All Groups</RouterLink>
            </div>
            <form @submit.prevent="addGroup">
              <input
                  placeholder="Group Name"
                  class="form-control animated fadeInUp"
                  type="text"
                  name="name"
                  v-model="name"
              />

              <br />
              <button class="btn w-100 animated fadeInUp float-end btn-primary">
                Add Group
              </button>
            </form>
          </div>

          </div>
      </div>
    </div>
  </div>
</template>
<style scoped></style>