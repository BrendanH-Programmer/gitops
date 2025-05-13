provider "azurerm" {
  client_id       = var.client_id
  client_secret   = var.client_secret
  tenant_id       = var.tenant_id
  subscription_id = var.subscription_id

  features {}
}

resource "azurerm_resource_group" "example" {
-  name     = "example-resources"
+  name     = "example-resources-v2"   # new name
  location = "UK SOUTH"
}