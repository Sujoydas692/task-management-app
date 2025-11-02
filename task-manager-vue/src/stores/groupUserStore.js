import { defineStore } from "pinia";
import apiClient from "../services/axiosClient";
import cogoToast from "cogo-toast";

export const useGroupUserStore = defineStore("groupUserStore", () => {
  const getGroupUsers = async (groupId) => {
    try {
      const res = await apiClient.get(`/groups/${groupId}/users`);
      return res.data.data.data || [];
    } catch (error) {
      cogoToast.error("Failed to fetch group users", { position: "top-right" });
      return [];
    }
  };

  const addUserToGroup = async (groupId, userId) => {
    try {
      await apiClient.post(`/groups/${groupId}/users`, {
        user_id: userId,
      });
      cogoToast.success("Add User To Group Successful", {
        position: "top-right",
      });
      return true;
    } catch (error) {
      cogoToast.error("Failed to add user to group", { position: "top-right" });
      return false;
    }
  };

  const removeUserFromGroup = async (groupId, userId) => {
    try {
      await apiClient.delete(`/groups/${groupId}/users/${userId}`);
      cogoToast.success("Remove User From Group Successful", {
        position: "top-right",
      });
      return true;
    } catch (error) {
      cogoToast.error("Failed to remove user from group", {
        position: "top-right",
      });
      return false;
    }
  };
  const getAllUsers = async () => {
    try {
      const res = await apiClient.get("/auth/users");
      return res.data.data || [];
    } catch (error) {
      cogoToast.error("Failed to load all users", { position: "top-right" });
      return [];
    }
  };
  const getGroupDetails = async (groupId) => {
    try {
      const res = await apiClient.get(`/groups/${groupId}`);
      return res.data.data;
    } catch (error) {
      cogoToast.error("Failed to fetch group details", { position: "top-right", });
      return [];
    }
  };
  return {
    getGroupUsers,
    addUserToGroup,
    removeUserFromGroup,
    getAllUsers,
    getGroupDetails,
  };
});
