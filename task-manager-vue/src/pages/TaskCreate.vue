<script setup>
import { ref } from 'vue';
import { createPinia, setActivePinia } from 'pinia'
import { useTaskStore } from '../stores/taskStore';
import { useRouter } from 'vue-router';


const title = ref('');
const description = ref('');

setActivePinia(createPinia());
const taskStore = useTaskStore();
const router = useRouter();

const addTask = async () => {
  const success = await taskStore.createTask({
    title: title.value,
    description: description.value,
  });
  if (success) {
    router.push({ name: 'tasks' });
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
              <h4>Task Create</h4>
              <RouterLink :to="{ name: 'tasks'}" class="btn btn-sm btn-primary">All tasks</RouterLink>
            </div>
            <form @submit.prevent="addTask">
              <input
                  placeholder="Title"
                  class="form-control animated fadeInUp"
                  type="text"
                  name="title"
                  v-model="title"
              />
              <br />

              <textarea
                placeholder="Task Description"
                class="form-control"
                name="description"
                v-model="description"
              >
              </textarea>

              <br />
              <button class="btn w-100 animated fadeInUp float-end btn-primary">
                Add Task
              </button>
            </form>
          </div>

          </div>
      </div>
    </div>
  </div>
</template>
<style scoped></style>