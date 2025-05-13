provider "azurerm" {
  client_id       = var.client_id
  client_secret   = var.client_secret
  tenant_id       = var.tenant_id
  subscription_id = var.subscription_id
  features {}
}

variable "resource_group_name" {
  description = "The name of the resource group"
  type        = string
  default     = "example-resources"  # Default value, or you can specify during apply
}

resource "azurerm_resource_group" "example" {
-  name     = "example-resources"
+  name     = "example-resources-v2"   # new name
  location = "UK SOUTH"
}