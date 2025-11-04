<script setup>
import { ref, onMounted, computed  } from "vue";
import { useRoute } from "vue-router";
import { createPinia, setActivePinia } from "pinia";
import { useGroupUserStore } from "../stores/groupUserStore";
import cogoToast from "cogo-toast";
import { useAuthStore } from "../stores/authStore";
import Swal from "sweetalert2";

setActivePinia(createPinia());
const route = useRoute();
const groupId = route.params.id;

const groupUserStore = useGroupUserStore();
const authStore = useAuthStore();

const users = ref([]);
const newUserId = ref("");
const allUsers = ref([]);
const groupName = ref("");

const GROUP_INFO_KEY = `group_${groupId}_info`;
const GROUP_USERS_KEY = `group_${groupId}_users`;
const ALL_USERS_KEY = "all_users_cache";

const fetchGroupName = async () => {
  const cached = localStorage.getItem(GROUP_INFO_KEY);
  if (cached) {
    groupName.value = JSON.parse(cached).name;
  }

  try {
    const group = await groupUserStore.getGroupDetails(groupId);
    if (group && group.name) {
      groupName.value = group.name;
      localStorage.setItem(GROUP_INFO_KEY, JSON.stringify(group));
    }
  } catch (error) {
    console.error("Group name load error:", error);
  }
};

const fetchGroupUsers = async () => {
  const cached = localStorage.getItem(GROUP_USERS_KEY);
  if (cached) {
    users.value = JSON.parse(cached);
  }

  try {
    const freshUsers = await groupUserStore.getGroupUsers(groupId);
    if (freshUsers) {
      users.value = freshUsers;
      localStorage.setItem(GROUP_USERS_KEY, JSON.stringify(freshUsers));
    }
  } catch (error) {
    console.error("Group users load error:", error);
  }
};

const addUserToGroup = async () => {
  if (!newUserId.value) {
    cogoToast.warn("Please select a user");
    return;
  }

  const success = await groupUserStore.addUserToGroup(groupId, newUserId.value);
  if (success) {
    newUserId.value = "";
    await fetchGroupUsers();
    await fetchAllUsers();
  }
};

const isDropdownDisabled = computed(() => allUsers.value.length === 0);

const removeUser = async (userId) => {
  const result = await Swal.fire({
    title: "Are you sure?",
    text: "You won't be able to revert this!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Yes, delete it!",
  });
  if (result.isConfirmed) {
    const success = await groupUserStore.removeUserFromGroup(groupId, userId);
    if (success) {
      await fetchGroupUsers();
      await fetchAllUsers();
    }
  }
};

const fetchAllUsers = async () => {
  const cached = localStorage.getItem(ALL_USERS_KEY);
  if (cached) {
    allUsers.value = JSON.parse(cached);
  }

  try {
    const all = await groupUserStore.getAllUsers();
    const assignedUserIds = users.value.map((u) => u.id);
    const authUserId =
      Number(localStorage.getItem("user_id")) ||
      Number(authStore?.user?.id);

    const filtered = all.filter(
      (user) =>
        user.id !== authUserId &&
        !user.group_id &&
        !assignedUserIds.includes(user.id)
    );

    allUsers.value = filtered;
    localStorage.setItem(ALL_USERS_KEY, JSON.stringify(filtered));
  } catch (error) {
    console.error("All users load error:", error);
  }
};

onMounted(async () => {
  await fetchGroupName();
  await fetchGroupUsers();
  await fetchAllUsers();
});
</script>

<template>
  <div class="content">
    <div class="container py-4">
      <div class="card p-4">
        <h4 class="mb-3">Group Users</h4>

        <div class="d-flex mb-4">
          <input
            type="text"
            :value="groupName"
            class="form-control me-2"
            style="max-width: 250px"
            readonly
          />
          <select
            v-model="newUserId"
            class="form-select me-2"
            style="max-width: 250px"
            :disabled="isDropdownDisabled"
          >
            <option value="">Select a user</option>
            <option v-for="user in allUsers" :key="user.id" :value="user.id">
              {{ user.name }} ({{ user.email }})
            </option>
          </select>
          <button @click="addUserToGroup" class="btn btn-primary" :disabled="isDropdownDisabled">
            Add User
          </button>
        </div>

        <table class="table table-striped">
          <thead>
            <tr>
              <th>User Name</th>
              <th>Email</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td>{{ user.name }}</td>
              <td>{{ user.email }}</td>
              <td>
                <button
                  @click="removeUser(user.id)"
                  class="btn btn-sm btn-danger"
                >
                  Remove
                </button>
              </td>
            </tr>
            <tr v-if="users.length === 0">
              <td colspan="3" class="text-center text-muted">
                No users in this group
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
<style scoped></style>
