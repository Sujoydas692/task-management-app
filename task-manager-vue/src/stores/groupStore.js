import { defineStore } from "pinia";
import { ref } from "vue";
import apiClient from "../services/axiosClient";
import cogoToast from "cogo-toast";
import { useRouter } from "vue-router";

export const useGroupStore = defineStore("group", () => {
    const getGroups = async () => {
        try {
            const res = await apiClient.get("/groups");
            return res.data.data.data;
        } catch (error) {
            cogoToast.error("Internal Server Error", { position: "top-right" });
            return [];
        }
    };

    const createGroup = async (taskData) => {
        try {
            await apiClient.post("/groups", taskData);
            cogoToast.success("Group Created Successful", { position: "top-right" });
            return true;
        } catch (error) {
            cogoToast.error("Internal Server Error", { position: "top-right" });
            return false;
        }
    };

    const updateGroup = async (id, groupData) => {
    try {
      await apiClient.put(`/groups/${id}`, groupData);
      cogoToast.success("Group Updated Successful", { position: "top-right" });
      return true;
    } catch (error) {
      cogoToast.error("Failed to Update Group", { position: "top-right" });
      return false;
    }
  };

   const deleteGroup = async (id) => {
    try {
      await apiClient.delete(`/groups/${id}`);
      cogoToast.success("Group Deleted Successful", { position: "top-right" });
      return true;
    } catch (error) {
      cogoToast.error("Failed to Delete Group", { position: "top-right" });
      return false;
    }
  };

    return{ 
        getGroups,
        createGroup,
        updateGroup,
        deleteGroup
     };
});